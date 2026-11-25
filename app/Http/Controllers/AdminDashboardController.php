<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Invoice statistics
        $totalInvoices = Invoice::count();

        $paidInvoices = Invoice::where('status', 'paid')->count();

        $unpaidInvoices = Invoice::where('status', 'unpaid')->count();

        $overdueInvoices = Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('due_date', '<', Carbon::today())
            ->count();

        // Customer statistics
        $totalCustomers = Customer::count();

        $completedPayments = DB::table('payments')
            ->select('invoice_id', DB::raw('SUM(amount) as paid_total'))
            ->where('status', 'completed')
            ->groupBy('invoice_id');

        $currencyTotals = Invoice::query()
            ->leftJoinSub($completedPayments, 'completed_payments', function ($join): void {
                $join->on('invoices.id', '=', 'completed_payments.invoice_id');
            })
            ->select('invoices.currency_code')
            ->selectRaw('SUM(invoices.total) as total_invoiced')
            ->selectRaw('SUM(COALESCE(completed_payments.paid_total, 0)) as paid_total')
            ->selectRaw(
                'SUM(CASE WHEN invoices.total > COALESCE(completed_payments.paid_total, 0) '
                .'THEN invoices.total - COALESCE(completed_payments.paid_total, 0) ELSE 0 END) '
                .'as outstanding_total'
            )
            ->groupBy('invoices.currency_code')
            ->orderBy('invoices.currency_code')
            ->get();

        // Monthly invoice activity for the current year
        $monthExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', created_at) AS INTEGER)"
            : 'MONTH(created_at)';

        $monthlyInvoices = Invoice::select(
            DB::raw($monthExpression.' as month'),
            DB::raw('COUNT(*) as total')
        )
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy(DB::raw($monthExpression))
            ->orderBy('month')
            ->get();

        // Recent invoices
        $recentInvoices = Invoice::with('customer')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalInvoices',
            'paidInvoices',
            'unpaidInvoices',
            'overdueInvoices',
            'totalCustomers',
            'currencyTotals',
            'monthlyInvoices',
            'recentInvoices'
        ));
    }
}
