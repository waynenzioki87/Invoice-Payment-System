<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 34px 40px; }
        body { color: #243047; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; }
        .toolbar { margin-bottom: 20px; text-align: right; }
        .toolbar a, .toolbar button { background: #176fd1; border: 0; border-radius: 4px; color: #fff; cursor: pointer; display: inline-block; font-size: 11px; margin-left: 6px; padding: 8px 12px; text-decoration: none; }
        .brand { color: #176fd1; font-size: 23px; font-weight: bold; }
        .muted { color: #718096; }
        .header { border-bottom: 2px solid #176fd1; margin-bottom: 22px; padding-bottom: 16px; }
        .header-table, .meta-table, .items, .totals { width: 100%; }
        .header-table td { vertical-align: top; width: 50%; }
        .invoice-title { color: #152238; font-size: 27px; font-weight: bold; text-align: right; }
        .meta-table { margin-bottom: 22px; }
        .meta-table td { vertical-align: top; width: 50%; }
        h3 { color: #4a5568; font-size: 10px; letter-spacing: .08em; margin: 0 0 7px; text-transform: uppercase; }
        .items { border-collapse: collapse; margin-top: 20px; }
        .items th { background: #edf4fc; color: #263750; font-size: 9px; text-align: left; text-transform: uppercase; }
        .items th, .items td { border-bottom: 1px solid #e4e9f0; padding: 10px 8px; }
        .right { text-align: right !important; }
        .totals { margin-top: 15px; }
        .totals td { padding: 5px 8px; }
        .grand-total { border-top: 2px solid #176fd1; color: #176fd1; font-size: 15px; font-weight: bold; }
        .status { background: #edf4fc; border-radius: 12px; display: inline-block; padding: 3px 9px; }
        .notes { background: #f7f9fc; margin-top: 24px; padding: 12px; }
        .footer { border-top: 1px solid #e4e9f0; color: #718096; margin-top: 35px; padding-top: 10px; text-align: center; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
    @php
        $downloadRoute = auth()->user()?->role === 'customer'
            ? 'customer.invoices.pdf'
            : 'invoices.pdf';
    @endphp
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route($downloadRoute, $invoice) }}">Download PDF</a>
    </div>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand">{{ $businessSettings?->business_name ?? config('app.name', 'INVOICEX') }}</div>
                    <div class="muted">Invoice management</div>
                </td>
                <td>
                    <div class="invoice-title">{{ $invoice->invoice_type_label }}</div>
                    <div class="right">Invoice # <strong>{{ $invoice->invoice_number }}</strong></div>
                    <div class="right">Status: <span class="status">{{ ucfirst($invoice->status) }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <h3>Bill to</h3>
                <strong>{{ $invoice->customer->name }}</strong><br>
                {{ $invoice->customer->email ?? '' }}<br>
                {{ $invoice->customer->phone }}<br>
                {{ $invoice->customer->address ?? '' }}
            </td>
            <td class="right">
                <h3>Invoice dates</h3>
                Issue date: {{ $invoice->issue_date->format('d M Y') }}<br>
                Due date: {{ $invoice->due_date->format('d M Y') }}<br>
                Currency: {{ $invoice->currency_code }}
                @if($invoice->relatedInvoice)
                    <br>Related invoice: {{ $invoice->relatedInvoice->invoice_number }}
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit price</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ $invoice->currency_symbol }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="right">{{ $invoice->currency_symbol }} {{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td></td>
            <td class="right">Subtotal</td>
            <td class="right">{{ $invoice->currency_symbol }} {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="right">Tax</td>
            <td class="right">{{ $invoice->currency_symbol }} {{ number_format($invoice->tax, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td></td>
            <td class="right">Total</td>
            <td class="right">{{ $invoice->currency_symbol }} {{ number_format($invoice->total, 2) }} {{ $invoice->currency_code }}</td>
        </tr>
        @php
            $paidTotal = $invoice->payments->where('status', 'completed')->sum('amount');
            $balanceDue = max(0, (float) $invoice->total - (float) $paidTotal);
        @endphp
        <tr>
            <td></td>
            <td class="right">Paid</td>
            <td class="right">{{ $invoice->currency_symbol }} {{ number_format($paidTotal, 2) }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="right"><strong>Balance due</strong></td>
            <td class="right"><strong>{{ $invoice->currency_symbol }} {{ number_format($balanceDue, 2) }}</strong></td>
        </tr>
    </table>

    @if($invoice->notes)
        <div class="notes"><strong>Notes</strong><br>{{ $invoice->notes }}</div>
    @endif

    <div class="footer">Thank you for your business.</div>
</body>
</html>
