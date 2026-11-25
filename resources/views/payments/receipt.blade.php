<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Receipt {{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { margin: 38px 44px; }
        body { color: #263248; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.55; }
        .toolbar { margin-bottom: 20px; text-align: right; }
        .toolbar a, .toolbar button { background: #176fd1; border: 0; border-radius: 4px; color: #fff; cursor: pointer; display: inline-block; font-size: 11px; margin-left: 6px; padding: 8px 12px; text-decoration: none; }
        .receipt { border: 1px solid #dfe6ef; border-radius: 8px; margin: 25px auto; max-width: 650px; padding: 30px; }
        .heading { border-bottom: 2px solid #176fd1; margin-bottom: 22px; padding-bottom: 16px; }
        .business { color: #176fd1; font-size: 22px; font-weight: bold; }
        .receipt-title { color: #18243a; font-size: 25px; font-weight: bold; margin-top: 20px; }
        .muted { color: #718096; }
        table { border-collapse: collapse; width: 100%; }
        td { border-bottom: 1px solid #e8edf3; padding: 9px 5px; }
        td:first-child { color: #718096; width: 40%; }
        .amount { background: #f0f7ff; border: 0; color: #176fd1; font-size: 18px; font-weight: bold; margin: 20px 0; padding: 16px; text-align: center; }
        .footer { color: #718096; margin-top: 25px; text-align: center; }
        @media print { .toolbar { display: none; } body { margin: 0; } .receipt { border: 0; } }
    </style>
</head>
<body>
    @php
        $downloadRoute = auth()->user()?->role === 'customer'
            ? 'customer.payments.receipt'
            : 'payments.receipt';
    @endphp
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <a href="{{ route($downloadRoute, $payment) }}">Download PDF</a>
    </div>

    <main class="receipt">
        <header class="heading">
            <div class="business">{{ $businessSettings?->business_name ?? config('app.name', 'INVOICEX') }}</div>
            <div class="receipt-title">Payment receipt</div>
            <div class="muted">Receipt no. RCPT-{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</div>
        </header>

        <div class="amount">
            {{ $payment->invoice->currency_symbol }} {{ number_format($payment->amount, 2) }}
            {{ $payment->invoice->currency_code }}
        </div>

        <table>
            <tr><td>Customer</td><td><strong>{{ $payment->invoice->customer->name }}</strong></td></tr>
            <tr><td>Customer email</td><td>{{ $payment->invoice->customer->email ?? 'Not provided' }}</td></tr>
            <tr><td>Invoice number</td><td>{{ $payment->invoice->invoice_number }}</td></tr>
            <tr><td>Invoice total</td><td>{{ $payment->invoice->currency_symbol }} {{ number_format($payment->invoice->total, 2) }}</td></tr>
            <tr><td>Payment date</td><td>{{ $payment->payment_date?->format('d M Y') ?? 'N/A' }}</td></tr>
            <tr><td>Payment method</td><td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td></tr>
            <tr><td>Transaction reference</td><td>{{ $payment->transaction_reference ?: 'N/A' }}</td></tr>
            <tr><td>Received by</td><td>{{ $payment->receivedBy->name ?? 'N/A' }}</td></tr>
            <tr><td>Status</td><td><strong>{{ ucfirst($payment->status) }}</strong></td></tr>
        </table>

        <div class="footer">This receipt confirms a completed payment. Thank you.</div>
    </main>
</body>
</html>
