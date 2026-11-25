<x-app-layout>
    <div class="container-fluid">
        <div class="mb-4">
            <h1 class="section-heading mb-1">{{ request()->routeIs('customer.receipts.index') ? 'My Receipts' : 'My Payments' }}</h1>
            <p class="section-description">Review payment activity for your invoices.</p>
        </div>

        <div class="card content-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Payment date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td class="fw-semibold">{{ $payment->invoice->invoice_number }}</td>
                                <td>{{ $payment->payment_date?->format('d M Y') ?? 'Awaiting confirmation' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>{{ $payment->transaction_reference ?: '—' }}</td>
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
                                <td class="text-nowrap">
                                    <a href="{{ route('customer.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    @if($payment->status === 'completed')
                                        <a href="{{ route('customer.payments.receipt', $payment) }}" class="btn btn-sm btn-outline-secondary">Receipt</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No payment records to display.</td></tr>
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
