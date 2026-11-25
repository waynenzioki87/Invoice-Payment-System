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

        $payment = DB::transaction(function () use ($validated, $phoneNumber, $accounting): Payment {
            $invoice = Invoice::whereKey($validated['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($invoice->currency_code !== 'KES') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'M-Pesa is available for KES invoices only.',
                ]);
            }

            if (! in_array($invoice->status, ['unpaid', 'partial', 'overdue'], true)) {
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
                'amount' => number_format($validated['amount'], 2, '.', ''),
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
        } catch (RuntimeException $exception) {
            $payment->update(['status' => 'failed']);

            return back()
                ->withInput()
                ->withErrors(['mpesa' => $exception->getMessage()]);
        }

        $payment->update([
            'checkout_request_id' => $response['CheckoutRequestID'],
            'merchant_request_id' => $response['MerchantRequestID'],
        ]);

        return redirect()
            ->route('payments.show', $payment)
            ->with('success', 'STK push sent. Complete the prompt on the customer’s phone.');
    }

    public function callback(
        Request $request,
        MpesaService $mpesa,
        InvoicePaymentService $accounting
    ): JsonResponse {
        $callback = $request->input('Body.stkCallback');
        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;

        if (! is_string($checkoutRequestId) || $checkoutRequestId === '') {
            Log::warning('Daraja callback missing checkout request ID.');

            return response()->json(['message' => 'Invalid callback payload.'], 422);
        }

        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();
        if (! $payment) {
            Log::warning('Daraja callback did not match a pending payment.', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json(['message' => 'Payment request not found.'], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json(['message' => 'Callback already processed.']);
        }

        $resultCode = $callback['ResultCode'] ?? null;
        if (! is_numeric($resultCode)) {
            Log::warning('Daraja callback had no result code.', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json(['message' => 'Invalid callback result.'], 422);
        }

        if ((int) $resultCode !== 0) {
            $payment->update(['status' => 'failed']);

            return response()->json(['message' => 'Payment was not completed.']);
        }

        try {
            $verification = $mpesa->queryStkPush($checkoutRequestId);
        } catch (RuntimeException $exception) {
            Log::error('Daraja payment verification request failed.', [
                'checkout_request_id' => $checkoutRequestId,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Payment verification is temporarily unavailable.'], 503);
        }

        if ((string) ($verification['ResultCode'] ?? '') !== '0') {
            Log::warning('Daraja query did not confirm the payment.', [
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $verification['ResultCode'] ?? null,
            ]);

            return response()->json(['message' => 'Payment is not confirmed.'], 202);
        }

        $metadata = collect($callback['CallbackMetadata']['Item'] ?? [])
            ->pluck('Value', 'Name');
        $amount = $metadata->get('Amount');
        $receipt = $metadata->get('MpesaReceiptNumber');
        $phone = $metadata->get('PhoneNumber');
        $transactionDate = $metadata->get('TransactionDate');

        if (! is_numeric($amount)
            || (int) $amount !== (int) $payment->amount
            || ! is_string($receipt)
            || $receipt === ''
            || ! is_numeric($phone)
            || $this->normalizePhone((string) $phone) !== $payment->phone_number) {
            Log::error('Daraja callback data did not match the pending payment.', [
                'checkout_request_id' => $checkoutRequestId,
                'payment_id' => $payment->id,
            ]);

            return response()->json(['message' => 'Payment details could not be verified.'], 422);
        }

        if ((! is_string($transactionDate) && ! is_int($transactionDate))
            || ! preg_match('/^\d{14}$/', (string) $transactionDate)) {
            Log::warning('Daraja callback had no transaction date.', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json(['message' => 'Invalid transaction date.'], 422);
        }

        $transactionDate = (string) $transactionDate;
        $paymentDate = Carbon::createFromFormat(
            '!YmdHis',
            $transactionDate,
            'Africa/Nairobi'
        );
        if (! $paymentDate || $paymentDate->format('YmdHis') !== $transactionDate) {
            Log::warning('Daraja callback had an invalid transaction date.', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return response()->json(['message' => 'Invalid transaction date.'], 422);
        }

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

            if (Payment::where('transaction_reference', $receipt)
                ->where('id', '<>', $lockedPayment->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'transaction_reference' => 'This M-Pesa receipt has already been used.',
                ]);
            }

            $lockedPayment->update([
                'transaction_reference' => $receipt,
                'payment_date' => $paymentDate,
                'status' => 'completed',
            ]);

            $invoice = Invoice::whereKey($lockedPayment->invoice_id)
                ->lockForUpdate()
                ->firstOrFail();
            $accounting->refreshStatus($invoice);

            $invoice->load('customer');
            if ($invoice->customer->email) {
                $recipient = $invoice->customer->email;
                $paymentForReceipt = $lockedPayment->fresh([
                    'invoice.customer',
                    'invoice.items',
                    'receivedBy',
                ]);
                DB::afterCommit(function () use ($recipient, $paymentForReceipt): void {
                    Mail::to($recipient)->queue(new PaymentReceiptMail($paymentForReceipt));
                });
            }
        });

        return response()->json(['message' => 'Payment confirmed.']);
    }

    private function normalizePhone(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if (str_starts_with($digits, '0')) {
            return '254'.substr($digits, 1);
        }

        if (str_starts_with($digits, '7') || str_starts_with($digits, '1')) {
            return '254'.$digits;
        }

        return $digits;
    }
}
