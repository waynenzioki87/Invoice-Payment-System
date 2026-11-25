<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $revenueByDate = Payment::query()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.status', 'completed')
            ->whereNotNull('payments.payment_date')
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('payments.payment_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('payments.payment_date', '<=', $to))
            ->selectRaw('payments.payment_date, invoices.currency_code, SUM(payments.amount) as total')
            ->groupBy('payments.payment_date', 'invoices.currency_code')
            ->orderByDesc('payments.payment_date')
            ->get();

        $paymentsByMethod = Payment::query()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.status', 'completed')
            ->whereNotNull('payments.payment_date')
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('payments.payment_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('payments.payment_date', '<=', $to))
            ->selectRaw('payments.payment_method, invoices.currency_code, COUNT(*) as transaction_count, SUM(payments.amount) as total')
            ->groupBy('payments.payment_method', 'invoices.currency_code')
            ->orderBy('payments.payment_method')
            ->get();

        $outstandingInvoices = $this->balanceQuery($filters)
            ->whereIn('invoices.status', ['unpaid', 'partial', 'overdue'])
            ->whereRaw('invoices.total > COALESCE(payment_totals.paid_total, 0)')
            ->orderBy('invoices.due_date')
            ->take(100)
            ->get();

        $overdueInvoices = $this->balanceQuery($filters)
            ->whereIn('invoices.status', ['unpaid', 'partial', 'overdue'])
            ->whereDate('invoices.due_date', '<', now()->toDateString())
            ->whereRaw('invoices.total > COALESCE(payment_totals.paid_total, 0)')
            ->orderBy('invoices.due_date')
            ->take(100)
            ->get();

        $customerSummary = DB::table('invoices')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('invoices.issue_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('invoices.issue_date', '<=', $to))
            ->selectRaw('customers.id, customers.name, invoices.currency_code, COUNT(invoices.id) as invoice_count, SUM(invoices.total) as invoiced_total')
            ->groupBy('customers.id', 'customers.name', 'invoices.currency_code')
            ->orderBy('customers.name')
            ->get();

        return view('reports.index', compact(
            'filters',
            'revenueByDate',
            'paymentsByMethod',
            'outstandingInvoices',
            'overdueInvoices',
            'customerSummary'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'type' => ['required', Rule::in(['revenue', 'payments', 'outstanding', 'overdue', 'customers'])],
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        [$headers, $rows] = match ($filters['type']) {
            'revenue' => [
                ['Payment date', 'Currency', 'Revenue'],
                $this->revenueQuery($filters)->get()->map(
                    fn ($row) => [$row->payment_date, $row->currency_code, $row->total]
                ),
            ],
            'payments' => [
                ['Method', 'Currency', 'Transactions', 'Total'],
                $this->methodQuery($filters)->get()->map(
                    fn ($row) => [$row->payment_method, $row->currency_code, $row->transaction_count, $row->total]
                ),
            ],
            'outstanding', 'overdue' => [
                ['Invoice', 'Customer', 'Currency', 'Total', 'Paid', 'Balance', 'Due date'],
                $this->balanceQuery($filters)
                    ->whereIn('invoices.status', ['unpaid', 'partial', 'overdue'])
                    ->when(
                        $filters['type'] === 'overdue',
                        fn ($query) => $query->whereDate('invoices.due_date', '<', now()->toDateString())
                    )
                    ->whereRaw('invoices.total > COALESCE(payment_totals.paid_total, 0)')
                    ->get()
                    ->map(fn ($row) => [
                        $row->invoice_number,
                        $row->customer_name,
                        $row->currency_code,
                        $row->total,
                        $row->paid_total ?? 0,
                        $row->total - ($row->paid_total ?? 0),
                        $row->due_date,
                    ]),
            ],
            'customers' => [
                ['Customer', 'Currency', 'Invoices', 'Invoiced total'],
                $this->customerQuery($filters)->get()->map(
                    fn ($row) => [$row->name, $row->currency_code, $row->invoice_count, $row->invoiced_total]
                ),
            ],
        };

        $fileName = 'invoicex-' . $filters['type'] . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers);

            foreach ($rows as $row) {
                fputcsv($stream, array_map($this->safeCsvCell(...), $row));
            }

            fclose($stream);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function revenueQuery(array $filters): Builder
    {
        return Payment::query()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.status', 'completed')
            ->whereNotNull('payments.payment_date')
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('payments.payment_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('payments.payment_date', '<=', $to))
            ->selectRaw('payments.payment_date, invoices.currency_code, SUM(payments.amount) as total')
            ->groupBy('payments.payment_date', 'invoices.currency_code')
            ->orderByDesc('payments.payment_date');
    }

    private function methodQuery(array $filters): Builder
    {
        return Payment::query()
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->where('payments.status', 'completed')
            ->whereNotNull('payments.payment_date')
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('payments.payment_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('payments.payment_date', '<=', $to))
            ->selectRaw('payments.payment_method, invoices.currency_code, COUNT(*) as transaction_count, SUM(payments.amount) as total')
            ->groupBy('payments.payment_method', 'invoices.currency_code')
            ->orderBy('payments.payment_method');
    }

    private function balanceQuery(array $filters): Builder
    {
        $paymentTotals = DB::table('payments')
            ->select('invoice_id', DB::raw('SUM(amount) as paid_total'))
            ->where('status', 'completed')
            ->groupBy('invoice_id');

        return Invoice::query()
            ->leftJoinSub($paymentTotals, 'payment_totals', function ($join): void {
                $join->on('invoices.id', '=', 'payment_totals.invoice_id');
            })
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('invoices.issue_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('invoices.issue_date', '<=', $to))
            ->select([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.currency_code',
                'invoices.total',
                'invoices.due_date',
                'customers.name as customer_name',
                'payment_totals.paid_total',
            ]);
    }

    private function customerQuery(array $filters): Builder
    {
        return Invoice::query()
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('invoices.issue_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('invoices.issue_date', '<=', $to))
            ->selectRaw('customers.name, invoices.currency_code, COUNT(invoices.id) as invoice_count, SUM(invoices.total) as invoiced_total')
            ->groupBy('customers.id', 'customers.name', 'invoices.currency_code')
            ->orderBy('customers.name');
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^\s*[=+\-@]/', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
