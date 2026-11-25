<x-app-layout>
    <div class="container-fluid">
        <div class="mb-4">
            <h1 class="section-heading mb-1">Staff Dashboard</h1>
            <p class="section-description">Your invoice activity and recent payment transactions.</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <a href="{{ route('invoices.index') }}" class="text-decoration-none">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="text-muted small">Invoices created</div>
                        <div class="display-6 fw-bold text-dark">{{ $invoicesCreated }}</div>
                    </div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('payments.index') }}" class="text-decoration-none">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="text-muted small">Payments recorded</div>
                        <div class="display-6 fw-bold text-success">{{ $paymentsRecorded }}</div>
                    </div></div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('invoices.index') }}" class="text-decoration-none">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="text-muted small">Outstanding invoices</div>
                        <div class="display-6 fw-bold text-warning">{{ $outstandingInvoices }}</div>
                    </div></div>
                </a>
            </div>
        </div>

        <div class="card content-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0">Recent transactions</h2>
                    <a href="{{ route('payments.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Invoice</th><th>Customer</th><th>Date</th><th>Method</th><th>Amount</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($recentTransactions as $payment)
                                <tr>
                                    <td>{{ $payment->invoice->invoice_number }}</td>
                                    <td>{{ $payment->invoice->customer->name }}</td>
                                    <td>{{ $payment->payment_date?->format('d M Y') ?? 'Pending' }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td>{{ $payment->invoice->currency_symbol }} {{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        @if($payment->status === 'completed')
                                            <span class="badge text-bg-success">Completed</span>
                                        @elseif($payment->status === 'pending')
                                            <span class="badge text-bg-warning">Pending</span>
                                        @else
                                            <span class="badge text-bg-danger">Failed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No transactions recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
