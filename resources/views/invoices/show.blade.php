<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Details | INVOICEX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Invoice Details</h2>
            <p class="text-muted">View your invoice information.</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Back to Invoices</a>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-primary">
                <i class="bi bi-download me-1"></i> Download PDF
            </a>
            <a href="{{ route('invoices.print', $invoice) }}" class="btn btn-outline-primary" target="_blank" rel="noopener">
                <i class="bi bi-printer me-1"></i> Print
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <!-- Invoice and Customer Information -->
            <div class="row mb-4">

                <div class="col-md-6">
                    <h5>Invoice Information</h5>

                    <p>
                        <strong>Invoice Number:</strong>
                        {{ $invoice->invoice_number }}
                    </p>

                    <p>
                        <strong>Issue Date:</strong>
                        {{ $invoice->issue_date }}
                    </p>

                    <p>
                        <strong>Due Date:</strong>
                        {{ $invoice->due_date }}
                    </p>

                    <p>
                        <strong>Currency:</strong>
                        <span class="badge bg-secondary">
                            {{ $invoice->currency_code }}
                        </span>
                    </p>

                    <p>
                        <strong>Status:</strong>

                        @if($invoice->status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($invoice->due_date->isBefore(today()) && in_array($invoice->status, ['unpaid', 'partial', 'overdue'], true))
                            <span class="badge bg-danger">Overdue</span>
                        @else
                            <span class="badge bg-warning text-dark">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        @endif
                    </p>
                </div>

                <div class="col-md-6">
                    <h5>Customer Information</h5>

                    <p>
                        <strong>Name:</strong>
                        {{ $invoice->customer->name }}
                    </p>

                    <p>
                        <strong>Email:</strong>
                        {{ $invoice->customer->email ?? 'N/A' }}
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        {{ $invoice->customer->phone }}
                    </p>

                    <p>
                        <strong>Address:</strong>
                        {{ $invoice->customer->address ?? 'N/A' }}
                    </p>
                </div>

            </div>

            <hr>

            <!-- Invoice Items -->
            <h5>Invoice Items</h5>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($invoice->items as $item)

                            <tr>
                                <td>{{ $item->description }}</td>

                                <td>{{ $item->quantity }}</td>

                                <td>
                                    {{ $invoice->currency_symbol }}
                                    {{ number_format($item->unit_price, 2) }}
                                </td>

                                <td>
                                    {{ $invoice->currency_symbol }}
                                    {{ number_format($item->amount, 2) }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    No items have been added to this invoice yet.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>

                </table>
            </div>

            <!-- Invoice Totals -->
            <div class="text-end mt-4">

                <p>
                    <strong>Subtotal:</strong>
                    {{ $invoice->currency_symbol }}
                    {{ number_format($invoice->subtotal, 2) }}
                </p>

                <p>
                    <strong>Tax:</strong>
                    {{ $invoice->currency_symbol }}
                    {{ number_format($invoice->tax, 2) }}
                </p>

                <hr>

                <h4 class="text-primary">
                    <strong>Total:</strong>
                    {{ $invoice->currency_symbol }}
                    {{ number_format($invoice->total, 2) }}
                </h4>

            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Payment History</h5>
                <div class="d-flex gap-2">
                    @if($invoice->currency_code === 'KES' && in_array($invoice->status, ['unpaid', 'partial', 'overdue'], true))
                        <a href="{{ route('payments.create', ['invoice_id' => $invoice->id, 'payment_method' => 'mpesa']) }}"
                           class="btn btn-sm btn-outline-success">Initiate M-Pesa Payment</a>
                    @endif
                    <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-outline-primary">Record Payment</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->payments->sortByDesc('payment_date') as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('d M Y') ?? 'Awaiting confirmation' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>{{ $payment->transaction_reference ?: '—' }}</td>
                                <td>
                                    @if($payment->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($payment->status === 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ $invoice->currency_symbol }} {{ number_format($payment->amount, 2) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No payments recorded for this invoice.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Notes -->
            @if($invoice->notes)
                <hr>

                <h5>Notes</h5>
                <p>{{ $invoice->notes }}</p>
            @endif

            <!-- Edit Button -->
            <a href="{{ route('invoices.edit', $invoice->id) }}"
               class="btn btn-warning mt-3">
                Edit Invoice
            </a>
            <form action="{{ route('invoices.duplicate', $invoice) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary mt-3">
                    <i class="bi bi-files me-1"></i> Duplicate Invoice
                </button>
            </form>

        </div>
    </div>

</div>

</body>
</html>
