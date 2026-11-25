<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="section-heading mb-1">Reports & Analytics</h1>
                <p class="section-description">Revenue, payment activity, outstanding invoices, and customer summaries.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach(['revenue', 'payments', 'outstanding', 'overdue', 'customers'] as $type)
                    <a href="{{ route('reports.export', array_merge($filters, ['type' => $type])) }}"
                       class="btn btn-sm btn-outline-primary">Export {{ ucfirst($type) }} CSV</a>
                @endforeach
            </div>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end mb-4">
            <div class="col-sm-4 col-md-3">
                <label for="report-from" class="form-label">From date</label>
                <input id="report-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
            </div>
            <div class="col-sm-4 col-md-3">
                <label for="report-to" class="form-label">To date</label>
                <input id="report-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Apply dates</button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Revenue by payment date</h2>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Date</th><th>Currency</th><th class="text-end">Revenue</th></tr></thead>
                                <tbody>
                                    @forelse($revenueByDate as $row)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($row->payment_date)->format('d M Y') }}</td>
                                            <td>{{ $row->currency_code }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($row->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-4">No completed payments in this date range.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Payments by method</h2>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Method</th><th>Currency</th><th class="text-end">Transactions</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                    @forelse($paymentsByMethod as $row)
                                        <tr>
                                            <td>{{ ucwords(str_replace('_', ' ', $row->payment_method)) }}</td>
                                            <td>{{ $row->currency_code }}</td>
                                            <td class="text-end">{{ $row->transaction_count }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($row->total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">No completed payments in this date range.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Outstanding invoices</h2>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Invoice</th><th>Customer</th><th>Due</th><th class="text-end">Balance</th></tr></thead>
                                <tbody>
                                    @forelse($outstandingInvoices as $invoice)
                                        <tr>
                                            <td><a href="{{ route('invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                            <td>{{ $invoice->customer_name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
                                            <td class="text-end">{{ $invoice->currency_code }} {{ number_format(max(0, $invoice->total - ($invoice->paid_total ?? 0)), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">No outstanding invoices in this date range.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="card content-card h-100">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Overdue invoices</h2>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Invoice</th><th>Customer</th><th>Due</th><th class="text-end">Balance</th></tr></thead>
                                <tbody>
                                    @forelse($overdueInvoices as $invoice)
                                        <tr>
                                            <td><a href="{{ route('invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></td>
                                            <td>{{ $invoice->customer_name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</td>
                                            <td class="text-end text-danger">{{ $invoice->currency_code }} {{ number_format(max(0, $invoice->total - ($invoice->paid_total ?? 0)), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">No overdue invoices in this date range.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card content-card">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Customer invoice summary</h2>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Customer</th><th>Currency</th><th class="text-end">Invoices</th><th class="text-end">Invoiced total</th></tr></thead>
                                <tbody>
                                    @forelse($customerSummary as $row)
                                        <tr>
                                            <td>{{ $row->name }}</td>
                                            <td>{{ $row->currency_code }}</td>
                                            <td class="text-end">{{ $row->invoice_count }}</td>
                                            <td class="text-end">{{ number_format($row->invoiced_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">No customer invoices in this date range.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
