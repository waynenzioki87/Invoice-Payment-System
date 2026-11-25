<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="section-heading mb-1">Payment Details</h1>
                <p class="section-description">Invoice {{ $payment->invoice->invoice_number }}</p>
            </div>
            @if($payment->status === 'completed')
                <div class="d-flex gap-2">
                    <a href="{{ route('customer.payments.receipt', $payment) }}" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Download receipt
                    </a>
                    <a href="{{ route('customer.payments.receipt.print', $payment) }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                        <i class="bi bi-printer me-1"></i> Print
                    </a>
                </div>
            @endif
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card content-card">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted fw-normal">Receipt</dt>
                            <dd class="col-7">RCPT-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</dd>
                            <dt class="col-5 text-muted fw-normal">Invoice</dt>
                            <dd class="col-7">{{ $payment->invoice->invoice_number }}</dd>
                            <dt class="col-5 text-muted fw-normal">Amount</dt>
                            <dd class="col-7 fw-bold">{{ $payment->invoice->currency_symbol }} {{ number_format($payment->amount, 2) }} {{ $payment->invoice->currency_code }}</dd>
                            <dt class="col-5 text-muted fw-normal">Method</dt>
                            <dd class="col-7">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</dd>
                            <dt class="col-5 text-muted fw-normal">Reference</dt>
                            <dd class="col-7">{{ $payment->transaction_reference ?: 'N/A' }}</dd>
                            <dt class="col-5 text-muted fw-normal">Date</dt>
                            <dd class="col-7">{{ $payment->payment_date?->format('d M Y') ?? 'Awaiting confirmation' }}</dd>
                            <dt class="col-5 text-muted fw-normal">Status</dt>
                            <dd class="col-7">
                                @if($payment->status === 'completed')
                                    <span class="badge text-bg-success">Completed</span>
                                @elseif($payment->status === 'pending')
                                    <span class="badge text-bg-warning">Pending</span>
                                @else
                                    <span class="badge text-bg-danger">Failed</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card content-card">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Invoice payment history</h2>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead><tr><th>Date</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
                                <tbody>
                                    @forelse($payment->invoice->payments as $invoicePayment)
                                        <tr>
                                            <td>{{ $invoicePayment->payment_date?->format('d M Y') ?? 'N/A' }}</td>
                                            <td>{{ $invoicePayment->transaction_reference ?: '—' }}</td>
                                            <td class="text-end">{{ $payment->invoice->currency_symbol }} {{ number_format($invoicePayment->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-4">No completed payments found.</td></tr>
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
