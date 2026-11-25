<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CustomerAccountService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\Response;

class CustomerPortalController extends Controller
{
    public function __construct(
        private readonly CustomerAccountService $customerAccounts
    ) {}

    public function invoices(): View
    {
        $invoices = $this->customer()
            ->invoices()
            ->with('payments')
            ->latest()
            ->paginate(10);

        return view('customer.invoices.index', compact('invoices'));
    }

    public function invoice(Invoice $invoice): View
    {
        $invoice = $this->ownedInvoice($invoice)->load([
            'customer',
            'items',
            'payments' => fn ($query) => $query->orderByDesc('payment_date'),
        ]);

        return view('customer.invoices.show', compact('invoice'));
    }

    public function downloadInvoice(Invoice $invoice): Response
    {
        $invoice = $this->ownedInvoice($invoice)->load(['customer', 'items', 'payments']);
        $businessSettings = BusinessSetting::first();

        return Pdf::loadView('invoices.pdf', compact('invoice', 'businessSettings'))
            ->setPaper('a4')
            ->download('invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function printInvoice(Invoice $invoice): View
    {
        $invoice = $this->ownedInvoice($invoice)->load(['customer', 'items', 'payments']);
        $businessSettings = BusinessSetting::first();

        return view('invoices.pdf', compact('invoice', 'businessSettings'));
    }

    public function payments(): View
    {
        $payments = $this->customerPayments()
            ->with(['invoice.customer'])
            ->latest()
            ->paginate(10);

        return view('customer.payments.index', compact('payments'));
    }

    public function receipts(): View
    {
        $payments = $this->customerPayments()
            ->where('status', 'completed')
            ->with(['invoice.customer'])
            ->latest('payment_date')
            ->paginate(10);

        return view('customer.payments.index', compact('payments'));
    }

    public function payment(Payment $payment): View
    {
        $payment = $this->ownedPayment($payment)->load([
            'invoice.customer',
            'invoice.payments' => fn ($query) => $query
                ->where('status', 'completed')
                ->orderByDesc('payment_date'),
            'receivedBy',
        ]);

        return view('customer.payments.show', compact('payment'));
    }

    public function downloadReceipt(Payment $payment): Response
    {
        $payment = $this->ownedCompletedPayment($payment)->load([
            'invoice.customer',
            'invoice.items',
            'receivedBy',
        ]);
        $businessSettings = BusinessSetting::first();

        return Pdf::loadView('payments.receipt', compact('payment', 'businessSettings'))
            ->setPaper('a4')
            ->download('receipt-'.$payment->id.'.pdf');
    }

    public function printReceipt(Payment $payment): View
    {
        $payment = $this->ownedCompletedPayment($payment)->load([
            'invoice.customer',
            'invoice.items',
            'receivedBy',
        ]);
        $businessSettings = BusinessSetting::first();

        return view('payments.receipt', compact('payment', 'businessSettings'));
    }

    private function customer(): Customer
    {
        return $this->customerAccounts->forUser(auth()->user());
    }

    private function ownedInvoice(Invoice $invoice): Invoice
    {
        return Invoice::whereKey($invoice->id)
            ->where('customer_id', $this->customer()->id)
            ->firstOrFail();
    }

    private function customerPayments(): Builder
    {
        return Payment::whereHas('invoice', function ($query): void {
            $query->where('customer_id', $this->customer()->id);
        });
    }

    private function ownedPayment(Payment $payment): Payment
    {
        return $this->customerPayments()
            ->whereKey($payment->id)
            ->firstOrFail();
    }

    private function ownedCompletedPayment(Payment $payment): Payment
    {
        return $this->customerPayments()
            ->whereKey($payment->id)
            ->where('status', 'completed')
            ->firstOrFail();
    }
}
