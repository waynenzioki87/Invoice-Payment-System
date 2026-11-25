<x-app-layout>
    <div class="container-fluid">
        <div class="mb-4">
            <h1 class="section-heading mb-1">My Invoices</h1>
            <p class="section-description">Review your invoices and outstanding balances.</p>
        </div>

        <div class="card content-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Issue date</th>
                            <th>Due date</th>
                            <th>Total</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php
                                $paid = $invoice->payments->where('status', 'completed')->sum('amount');
                                $balance = max(0, (float) $invoice->total - (float) $paid);
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->issue_date->format('d M Y') }}</td>
                                <td>{{ $invoice->due_date->format('d M Y') }}</td>
                                <td>{{ $invoice->currency_symbol }} {{ number_format($invoice->total, 2) }}</td>
                                <td>{{ $invoice->currency_symbol }} {{ number_format($balance, 2) }}</td>
                                <td>
                                    @if($invoice->status === 'paid')
                                        <span class="badge text-bg-success">Paid</span>
                                    @elseif($invoice->due_date->isBefore(today()))
                                        <span class="badge text-bg-danger">Overdue</span>
                                    @else
                                        <span class="badge text-bg-warning">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('customer.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('customer.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">You do not have any invoices yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($invoices->hasPages())
                <div class="card-footer">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
