<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h1 class="section-heading mb-1">Invoice {{ $invoice->invoice_number }}</h1>
                <p class="section-description">{{ $invoice->invoice_type_label }} · Due {{ $invoice->due_date->format('d M Y') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('customer.invoices.pdf', $invoice) }}" class="btn btn-primary">
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
                <a href="{{ route('customer.invoices.print', $invoice) }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                    <i class="bi bi-printer me-1"></i> Print
                </a>
            </div>
        </div>

        @php
            $paidTotal = $invoice->payments->where('status', 'completed')->sum('amount');
            $balanceDue = max(0, (float) $invoice->total - (float) $paidTotal);
        @endphp

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card content-card mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Unit price</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoice->items as $item)
                                        <tr>
                                            <td>{{ $item->description }}</td>
                                            <td class="text-end">{{ $item->quantity }}</td>
                                            <td class="text-end">{{ $invoice->currency_symbol }} {{ number_format($item->unit_price, 2) }}</td>
                                            <td class="text-end fw-semibold">{{ $invoice->currency_symbol }} {{ number_format($item->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-4">No invoice items.</td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr><th colspan="3" class="text-end">Subtotal</th><td class="text-end">{{ $invoice->currency_symbol }} {{ number_format($invoice->subtotal, 2) }}</td></tr>
                                    <tr><th colspan="3" class="text-end">Tax</th><td class="text-end">{{ $invoice->currency_symbol }} {{ number_format($invoice->tax, 2) }}</td></tr>
                                    <tr><th colspan="3" class="text-end">Invoice total</th><td class="text-end fw-bold">{{ $invoice->currency_symbol }} {{ number_format($invoice->total, 2) }}</td></tr>
                                    <tr><th colspan="3" class="text-end">Paid</th><td class="text-end">{{ $invoice->currency_symbol }} {{ number_format($paidTotal, 2) }}</td></tr>
                                    <tr><th colspan="3" class="text-end">Balance due</th><td class="text-end fw-bold text-primary">{{ $invoice->currency_symbol }} {{ number_format($balanceDue, 2) }}</td></tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @if($invoice->notes)
                    <div class="card content-card">
                        <div class="card-body">
                            <h2 class="h6 fw-bold">Notes</h2>
                            <p class="mb-0">{{ $invoice->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card content-card">
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-3">Payment history</h2>
                        @forelse($invoice->payments->where('status', 'completed') as $payment)
                            <div class="d-flex justify-content-between gap-2 border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">{{ $payment->payment_date?->format('d M Y') ?? 'N/A' }}</div>
                                    <small class="text-muted">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">{{ $invoice->currency_symbol }} {{ number_format($payment->amount, 2) }}</div>
                                    <a class="small" href="{{ route('customer.payments.receipt', $payment) }}">Receipt</a>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">No completed payments recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
