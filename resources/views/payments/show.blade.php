<x-app-layout>
    <div class="container-fluid">
        <div class="mb-4">
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Payments
            </a>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="section-heading mb-1">Payment Details</h1>
                <p class="section-description">Receipt reference: <span class="fw-semibold">RCPT-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</span></p>
            </div>
            @if($payment->status === 'completed')
                <div class="d-flex gap-2">
                    <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-primary">
                        <i class="bi bi-download me-1"></i> Download receipt
                    </a>
                    <a href="{{ route('payments.receipt.print', $payment) }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                        <i class="bi bi-printer me-1"></i> Print receipt
                    </a>
                </div>
            @endif
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card content-card h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 fw-bold mb-3">Payment information</h2>
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted fw-normal">Invoice</dt>
                            <dd class="col-sm-7 fw-semibold">{{ $payment->invoice->invoice_number }}</dd>
                            <dt class="col-sm-5 text-muted fw-normal">Customer</dt>
                            <dd class="col-sm-7">{{ $payment->invoice->customer->name }}</dd>
                            <dt class="col-sm-5 text-muted fw-normal">Amount paid</dt>
                            <dd class="col-sm-7 fw-bold text-primary">
                                {{ $payment->invoice->currency_symbol }} {{ number_format($payment->amount, 2) }}
                                {{ $payment->invoice->currency_code }}
                            </dd>
                            <dt class="col-sm-5 text-muted fw-normal">Method</dt>
                            <dd class="col-sm-7">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</dd>
                            <dt class="col-sm-5 text-muted fw-normal">Reference</dt>
                            <dd class="col-sm-7">{{ $payment->transaction_reference ?: 'N/A' }}</dd>
                            <dt class="col-sm-5 text-muted fw-normal">Payment date</dt>
                            <dd class="col-sm-7">{{ $payment->payment_date?->format('d M Y') ?? 'N/A' }}</dd>
                            <dt class="col-sm-5 text-muted fw-normal">Received by</dt>
                            <dd class="col-sm-7">{{ $payment->receivedBy->name ?? 'N/A' }}</dd>
                            <dt class="col-sm-5 text-muted fw-normal">Status</dt>
                            <dd class="col-sm-7">
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h6 fw-bold mb-0">Invoice payment history</h2>
                            <span class="small text-muted">{{ $payment->invoice->payments->count() }} completed</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($payment->invoice->payments as $invoicePayment)
                                        <tr>
                                            <td>{{ $invoicePayment->payment_date?->format('d M Y') ?? 'N/A' }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $invoicePayment->payment_method)) }}</td>
                                            <td>{{ $invoicePayment->transaction_reference ?: '—' }}</td>
                                            <td class="text-end fw-semibold">
                                                {{ $payment->invoice->currency_symbol }} {{ number_format($invoicePayment->amount, 2) }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">No completed payments found.</td></tr>
                                    @endforelse
                                </tbody>
                                @if($payment->invoice->payments->isNotEmpty())
                                    @php
                                        $paidAmount = $payment->invoice->payments->sum('amount');
                                        $remaining = max(0, (float) $payment->invoice->total - (float) $paidAmount);
                                    @endphp
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Total paid</th>
                                            <th class="text-end">{{ $payment->invoice->currency_symbol }} {{ number_format($paidAmount, 2) }}</th>
                                        </tr>
                                        <tr>
                                            <th colspan="3">Outstanding</th>
                                            <th class="text-end">{{ $payment->invoice->currency_symbol }} {{ number_format($remaining, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
