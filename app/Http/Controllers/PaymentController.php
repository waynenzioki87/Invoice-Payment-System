<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptMail;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoicePaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    // Display all payments
    public function index(Request $request)
    {
        $validated = $request->validate([
            'method' => 'nullable|in:cash,bank_transfer,card,mpesa',
            'reference' => 'nullable|string|max:255',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $payments = Payment::with(['invoice.customer', 'receivedBy'])
            ->when($validated['method'] ?? null, fn ($query, string $method) => $query->where('payment_method', $method))
            ->when($validated['reference'] ?? null, fn ($query, string $reference) => $query->where('transaction_reference', 'like', '%'.$reference.'%'))
            ->when($validated['from'] ?? null, fn ($query, string $from) => $query->whereDate('payment_date', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, string $to) => $query->whereDate('payment_date', '<=', $to))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', compact('payments'));
    }

    // Show payment form
    public function create(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'payment_method' => ['nullable', Rule::in([
                'cash',
                'bank_transfer',
                'card',
                'mpesa',
            ])],
        ]);

        $invoices = Invoice::with('customer')
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->orderBy('created_at', 'desc')
            ->get();

        $invoices->each(function (Invoice $invoice): void {
            $invoice->paid_total = $invoice->payments()
                ->where('status', 'completed')
                ->sum('amount');
            $reservedAmount = $invoice->payments()
                ->where('status', 'pending')
                ->sum('amount');
            $invoice->balance_due = max(
                0,
                (float) $invoice->total
                    - (float) $invoice->paid_total
                    - (float) $reservedAmount
            );
        });

        $selectedInvoice = $invoices->firstWhere('id', (int) ($validated['invoice_id'] ?? 0));
        $selectedPaymentMethod = $validated['payment_method'] ?? '';
        $defaultAmount = $selectedInvoice
            ? number_format(
                $selectedPaymentMethod === 'mpesa'
                    ? floor($selectedInvoice->balance_due)
                    : $selectedInvoice->balance_due,
                $selectedPaymentMethod === 'mpesa' ? 0 : 2,
                '.',
                ''
            )
            : '';

        return view('payments.create', compact(
            'invoices',
            'selectedInvoice',
            'selectedPaymentMethod',
            'defaultAmount'
        ));
    }

    // Record a payment
    public function store(Request $request, InvoicePaymentService $accounting)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'payment_method' => ['required', Rule::in([
                'cash',
                'bank_transfer',
                'card',
            ])],
            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('payments', 'transaction_reference'),
            ],
            'payment_date' => 'required|date|before_or_equal:today',
        ]);

        $payment = DB::transaction(function () use ($validated, $accounting): Payment {
            $invoice = Invoice::where('id', $validated['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($invoice->status, ['unpaid', 'partial', 'overdue'], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'This invoice cannot receive payments.',
                ]);
            }

            $reservedTotal = $invoice->payments()
                ->whereIn('status', ['completed', 'pending'])
                ->sum('amount');

            $amountCents = $accounting->toCents($validated['amount']);
            $reservedCents = $accounting->toCents($reservedTotal);
            $totalCents = $accounting->toCents($invoice->total);
            $balanceCents = $totalCents - $reservedCents;

            if ($amountCents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the outstanding balance of '
                        .number_format(max(0, $balanceCents) / 100, 2),
                ]);
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'received_by' => Auth::id(),
                'amount' => number_format($amountCents / 100, 2, '.', ''),
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'payment_date' => $validated['payment_date'],
                'status' => 'completed',
            ]);

            $accounting->refreshStatus($invoice);

            return $payment;
        });

        $payment->load('invoice.customer');
        if ($payment->invoice->customer->email) {
            Mail::to($payment->invoice->customer->email)->queue(new PaymentReceiptMail($payment));
        }

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    // View payment details
    public function show(Payment $payment)
    {
        $payment->load([
            'invoice.customer',
            'invoice.payments' => fn ($query) => $query
                ->where('status', 'completed')
                ->orderByDesc('payment_date'),
            'receivedBy',
        ]);

        return view('payments.show', compact('payment'));
    }

    public function downloadReceipt(Payment $payment)
    {
        abort_unless($payment->status === 'completed', 404);
        $payment->load(['invoice.customer', 'invoice.items', 'receivedBy']);
        $businessSettings = BusinessSetting::first();

        return Pdf::loadView('payments.receipt', compact('payment', 'businessSettings'))
            ->setPaper('a4')
            ->download('receipt-'.$payment->id.'.pdf');
    }

    public function printReceipt(Payment $payment)
    {
        abort_unless($payment->status === 'completed', 404);
        $payment->load(['invoice.customer', 'invoice.items', 'receivedBy']);
        $businessSettings = BusinessSetting::first();

        return view('payments.receipt', compact('payment', 'businessSettings'));
    }
}
