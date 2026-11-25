<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\CustomerAccountService;

class CustomerDashboardController extends Controller
{
    public function index(CustomerAccountService $customerAccounts)
    {
        $customer = $customerAccounts->forUser(auth()->user());
        $invoices = $customer->invoices()->with('payments')->get();

        $outstandingByCurrency = $invoices
            ->groupBy('currency_code')
            ->map(function ($currencyInvoices): float {
                return $currencyInvoices->sum(function ($invoice): float {
                    $paid = $invoice->payments->where('status', 'completed')->sum('amount');

                    return max(0, (float) $invoice->total - (float) $paid);
                });
            })
            ->filter(fn (float $amount): bool => $amount > 0);

        $dueSoon = $invoices->filter(function ($invoice): bool {
            $paid = $invoice->payments->where('status', 'completed')->sum('amount');

            return (float) $paid < (float) $invoice->total
                && $invoice->due_date
                && $invoice->due_date->betweenIncluded(now()->startOfDay(), now()->addDays(30)->endOfDay());
        })->count();

        $recentPayments = Payment::whereHas('invoice', function ($query) use ($customer): void {
            $query->where('customer_id', $customer->id);
        })
            ->where('status', 'completed')
            ->with('invoice')
            ->latest('payment_date')
            ->take(5)
            ->get();

        return view('customer.dashboard', [
            'customer' => $customer,
            'invoiceCount' => $invoices->count(),
            'paidInvoiceCount' => $invoices->where('status', 'paid')->count(),
            'outstandingByCurrency' => $outstandingByCurrency,
            'dueSoonCount' => $dueSoon,
            'recentPayments' => $recentPayments,
        ]);
    }
}
