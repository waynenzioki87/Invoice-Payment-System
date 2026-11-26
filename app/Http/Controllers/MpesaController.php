<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoicePaymentService;
use App\Services\MpesaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class MpesaController extends Controller
{
    public function initiate(
        Request $request,
        MpesaService $mpesa,
        InvoicePaymentService $accounting
    ): RedirectResponse {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => ['required', 'integer', 'min:1'],
            'phone_number' => ['required', 'regex:/^(?:254|0)?[17]\d{8}$/'],
        ]);

        $phoneNumber = $this->normalizePhone($validated['phone_number']);

        $payment = DB::transaction(function () use (
            $validated,
            $phoneNumber,
            $accounting
        ): Payment {
            $invoice = Invoice::whereKey($validated['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($invoice->currency_code !== 'KES') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'M-Pesa is available for KES invoices only.',
                ]);
            }

            if (! in_array($invoice->status, [
                'unpaid',
                'partial',
                'overdue',
            ], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'This invoice cannot receive payments.',
                ]);
            }

            $committedCents = $accounting->toCents(
                $invoice->payments()
                    ->whereIn('status', ['completed', 'pending'])
                    ->sum('amount')
            );

            $amountCents = (int) $validated['amount'] * 100;
            $totalCents = $accounting->toCents($invoice->total);

            if ($amountCents > $totalCents - $committedCents) {
                throw ValidationException::withMessages([
                    'amount' => 'The M-Pesa amount cannot exceed the available invoice balance.',
                ]);
            }

            return Payment::create([
                'invoice_id' => $invoice->id,
                'received_by' => Auth::id(),
                'amount' => number_format(
                    $validated['amount'],
                    2,
                    '.',
                    ''
                ),
                'payment_method' => 'mpesa',
                'payment_date' => null,
                'phone_number' => $phoneNumber,
                'status' => 'pending',
            ]);
        });

        try {
            $response = $mpesa->initiateStkPush(
                $payment->invoice()->firstOrFail(),
                (int) $validated['amount'],
                $phoneNumber
            );

            $payment->update([
                'checkout_request_id' => $response['CheckoutRequestID'],
                'merchant_request_id' => $response['MerchantRequestID'],
            ]);

        } catch (RuntimeException $exception) {
            $payment->update(['status' => 'failed']);

            Log::error('M-Pesa STK initiation failed.', [
                'payment_id' => $payment->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['mpesa' => $exception->getMessage()]);
        }

        Log::info('M-Pesa STK push initiated.', [
            'payment_id' => $payment->id,
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID'],
        ]);

        return redirect()
            ->route('payments.show', $payment)
            ->with(
                'success',
                'STK push sent. Complete the prompt on the customer’s phone.'
            );
    }

    public function callback(
        Request $request,
        MpesaService $mpesa,
        InvoicePaymentService $accounting
    ): JsonResponse {

        // 1. Log every callback received.
        Log::info('MPESA CALLBACK RECEIVED', [
            'payload' => $request->all(),
            'raw_body' => $request->getContent(),
        ]);

        $callback = $request->input('Body.stkCallback');

        if (! is_array($callback)) {
            Log::warning('Invalid M-Pesa callback structure.');

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received.',
            ]);
        }

        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;

        if (! is_string($checkoutRequestId) || $checkoutRequestId === '') {
            Log::warning('M-Pesa callback missing CheckoutRequestID.');

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received.',
            ]);
        }

        // 2. Find the corresponding payment.
        $payment = Payment::where(
            'checkout_request_id',
            $checkoutRequestId
        )->first();

        if (! $payment) {
            Log::warning('M-Pesa callback payment not found.', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received.',
            ]);
        }

        // 3. Prevent duplicate processing.
        if ($payment->status === 'completed') {
            Log::info('M-Pesa callback already processed.', [
                'payment_id' => $payment->id,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback already processed.',
            ]);
        }

        $resultCode = $callback['ResultCode'] ?? null;

        if (! is_numeric($resultCode)) {
            Log::warning('M-Pesa callback missing ResultCode.', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received.',
            ]);
        }

        // 4. Handle failed or cancelled payments.
        if ((int) $resultCode !== 0) {
            Payment::whereKey($payment->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            Log::warning('M-Pesa payment failed or cancelled.', [
                'payment_id' => $payment->id,
                'result_code' => $resultCode,
                'description' => $callback['ResultDesc'] ?? null,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received.',
            ]);
        }

        // 5. Extract callback metadata.
        $metadata = collect(
            $callback['CallbackMetadata']['Item'] ?? []
        )->pluck('Value', 'Name');

        $amount = $metadata->get('Amount');
        $receipt = $metadata->get('MpesaReceiptNumber');
        $phone = $metadata->get('PhoneNumber');
        $transactionDate = $metadata->get('TransactionDate');

        if (
            ! is_numeric($amount) ||
            (float) $amount !== (float) $payment->amount ||
            ! is_string($receipt) ||
            $receipt === '' ||
            ! is_numeric($phone) ||
            $this->normalizePhone((string) $phone) !== $payment->phone_number
        ) {
            Log::error('M-Pesa payment details mismatch.', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
                'amount' => $amount,
                'receipt' => $receipt,
                'phone' => $phone,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received for investigation.',
            ]);
        }

        // 6. Validate transaction date.
        if (
            (! is_string($transactionDate) && ! is_int($transactionDate)) ||
            ! preg_match('/^\d{14}$/', (string) $transactionDate)
        ) {
            Log::warning('Invalid M-Pesa transaction date.', [
                'payment_id' => $payment->id,
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received.',
            ]);
        }

        try {
            $transactionDate = (string) $transactionDate;

            $paymentDate = Carbon::createFromFormat(
                '!YmdHis',
                $transactionDate,
                'Africa/Nairobi'
            );

            if (
                ! $paymentDate ||
                $paymentDate->format('YmdHis') !== $transactionDate
            ) {
                throw new RuntimeException(
                    'Invalid M-Pesa transaction date.'
                );
            }

            // 7. Verify transaction with Safaricom.
            $verification = $mpesa->queryStkPush(
                $checkoutRequestId
            );

            if ((string) ($verification['ResultCode'] ?? '') !== '0') {
                Log::warning('M-Pesa transaction not yet confirmed.', [
                    'payment_id' => $payment->id,
                    'verification' => $verification,
                ]);

                return response()->json([
                    'ResultCode' => 0,
                    'ResultDesc' => 'Callback received. Verification pending.',
                ]);
            }

            // 8. Update payment and invoice atomically.
            DB::transaction(function () use (
                $payment,
                $receipt,
                $paymentDate,
                $accounting
            ): void {

                $lockedPayment = Payment::whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPayment->status === 'completed') {
                    return;
                }

                if ($lockedPayment->status !== 'pending') {
                    Log::warning('Payment is no longer pending.', [
                        'payment_id' => $lockedPayment->id,
                        'status' => $lockedPayment->status,
                    ]);

                    return;
                }

                if (
                    Payment::where('transaction_reference', $receipt)
                        ->where('id', '<>', $lockedPayment->id)
                        ->exists()
                ) {
                    throw new RuntimeException(
                        'M-Pesa receipt has already been used.'
                    );
                }

                $lockedPayment->update([
                    'transaction_reference' => $receipt,
                    'payment_date' => $paymentDate,
                    'status' => 'completed',
                ]);

                $invoice = Invoice::whereKey(
                    $lockedPayment->invoice_id
                )->lockForUpdate()->firstOrFail();

                $accounting->refreshStatus($invoice);

                $invoice->load('customer');

                if ($invoice->customer && $invoice->customer->email) {
                    $recipient = $invoice->customer->email;

                    $paymentForReceipt = $lockedPayment->fresh([
                        'invoice.customer',
                        'invoice.items',
                        'receivedBy',
                    ]);

                    DB::afterCommit(function () use (
                        $recipient,
                        $paymentForReceipt
                    ): void {
                        Mail::to($recipient)->queue(
                            new PaymentReceiptMail($paymentForReceipt)
                        );
                    });
                }

                Log::info('M-Pesa payment successfully completed.', [
                    'payment_id' => $lockedPayment->id,
                    'invoice_id' => $invoice->id,
                    'receipt' => $receipt,
                ]);
            });

        } catch (Throwable $exception) {
            Log::error('M-Pesa callback processing failed.', [
                'payment_id' => $payment->id,
                'checkout_request_id' => $checkoutRequestId,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'Callback received. Processing requires investigation.',
            ]);
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Payment processed.',
        ]);
    }

    private function normalizePhone(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if (str_starts_with($digits, '0')) {
            return '254'.substr($digits, 1);
        }

        if (
            str_starts_with($digits, '7') ||
            str_starts_with($digits, '1')
        ) {
            return '254'.$digits;
        }

        return $digits;
    }
}
