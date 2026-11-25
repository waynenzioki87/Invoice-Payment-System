<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="section-heading mb-1">Payments</h1>
                <p class="section-description">Track and manage customer payments.</p>
            </div>
            <a href="{{ route('payments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Record Payment
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('payments.index') }}" class="row g-2 mb-3">
            <div class="col-sm-6 col-lg-2">
                <select name="method" class="form-select">
                    <option value="">All methods</option>
                    @foreach(['cash', 'bank_transfer', 'card', 'mpesa'] as $method)
                        <option value="{{ $method }}" @selected(request('method') === $method)>{{ ucwords(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <input type="search" name="reference" value="{{ request('reference') }}"
                       class="form-control" placeholder="Transaction reference">
            </div>
            <div class="col-sm-6 col-lg-2">
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="Payment date from">
            </div>
            <div class="col-sm-6 col-lg-2">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="Payment date to">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>

        <div class="card content-card">
            <div class="card-body border-bottom">
                <h2 class="h6 fw-semibold mb-0">Payment Transactions</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td class="fw-semibold">{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                                <td>{{ $payment->invoice->customer->name ?? 'N/A' }}</td>
                                <td class="fw-semibold">
                                    {{ $payment->invoice->currency_symbol ?? '' }} {{ number_format($payment->amount, 2) }}
                                    <small class="d-block text-muted">{{ $payment->invoice->currency_code ?? '' }}</small>
                                </td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>{{ $payment->transaction_reference ?? 'N/A' }}</td>
                                <td>{{ $payment->payment_date?->format('d M Y') ?? 'N/A' }}</td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="badge text-bg-success">Completed</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="badge text-bg-warning">Pending</span>
                                    @else
                                        <span class="badge text-bg-danger">Failed</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">No payment transactions recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="card-footer">{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
