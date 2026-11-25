<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffDashboardController extends Controller
{
    public function index()
    {
        $staffId = Auth::id();
        $invoicesCreated = Invoice::where('created_by', $staffId)->count();
        $paymentsRecorded = Payment::where('received_by', $staffId)->count();

        $completedPayments = DB::table('payments')
            ->select('invoice_id', DB::raw('SUM(amount) as paid_total'))
            ->where('status', 'completed')
            ->groupBy('invoice_id');

        $outstandingInvoices = Invoice::query()
            ->leftJoinSub($completedPayments, 'completed_payments', function ($join): void {
                $join->on('invoices.id', '=', 'completed_payments.invoice_id');
            })
            ->whereIn('invoices.status', ['unpaid', 'partial', 'overdue'])
            ->whereRaw('invoices.total > COALESCE(completed_payments.paid_total, 0)')
            ->count();

        $recentTransactions = Payment::where('received_by', $staffId)
            ->with(['invoice.customer'])
            ->latest()
            ->take(5)
            ->get();

        return view('staff.dashboard', compact(
            'invoicesCreated',
            'paymentsRecorded',
            'outstandingInvoices',
            'recentTransactions'
        ));
    }
}
