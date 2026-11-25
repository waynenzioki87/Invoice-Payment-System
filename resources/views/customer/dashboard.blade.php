<x-app-layout>
    <div class="container-fluid">
        <div class="mb-4">
            <h1 class="section-heading mb-1">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="section-description">Your invoices, outstanding balance, and recent payments.</p>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('customer.invoices.index') }}" class="text-decoration-none">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="text-muted small">My invoices</div>
                        <div class="display-6 fw-bold text-dark">{{ $invoiceCount }}</div>
                    </div></div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('customer.invoices.index') }}" class="text-decoration-none">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="text-muted small">Outstanding balance</div>
                        @forelse($outstandingByCurrency as $currency => $amount)
                            <div class="fw-bold text-primary">{{ $currency }} {{ number_format($amount, 2) }}</div>
                        @empty
                            <div class="h3 fw-bold text-primary mb-0">0.00</div>
                        @endforelse
                    </div></div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('customer.invoices.index') }}" class="text-decoration-none">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="text-muted small">Paid invoices</div>
                        <div class="display-6 fw-bold text-success">{{ $paidInvoiceCount }}</div>
                    </div></div>
                </a>
            </div>
            <div class="col-sm-6 col-xl-3">
                <a href="{{ route('customer.invoices.index') }}" class="text-decoration-none">
                    <div class="card content-card h-100"><div class="card-body">
                        <div class="text-muted small">Due in the next 30 days</div>
                        <div class="display-6 fw-bold text-warning">{{ $dueSoonCount }}</div>
                    </div></div>
                </a>
            </div>
        </div>

        <div class="card content-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h6 fw-bold mb-0">Recent payments</h2>
                    <a href="{{ route('customer.payments.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Invoice</th><th>Date</th><th>Method</th><th class="text-end">Amount</th><th></th></tr></thead>
                        <tbody>
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td class="fw-semibold">{{ $payment->invoice->invoice_number }}</td>
                                    <td>{{ $payment->payment_date?->format('d M Y') ?? 'N/A' }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                    <td class="text-end">{{ $payment->invoice->currency_symbol }} {{ number_format($payment->amount, 2) }}</td>
                                    <td><a href="{{ route('customer.payments.show', $payment) }}" class="btn btn-sm btn-outline-secondary">Details</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No completed payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
