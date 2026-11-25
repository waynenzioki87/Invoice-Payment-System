<!DOCTYPE html>
<html lang="en">
<body style="font-family:Arial,sans-serif;color:#243047;line-height:1.6">
    <h1>{{ $reminder ? 'Payment reminder' : 'A new invoice is available' }}</h1>
    <p>Hello {{ $invoice->customer->name }},</p>
    @if($reminder)
        <p>This is a reminder that invoice <strong>{{ $invoice->invoice_number }}</strong> has an outstanding balance.</p>
    @else
        <p>Invoice <strong>{{ $invoice->invoice_number }}</strong> has been issued to you.</p>
    @endif
    <p>
        Total: {{ $invoice->currency_code }} {{ number_format($invoice->total, 2) }}<br>
        Due: {{ $invoice->due_date->format('d M Y') }}
    </p>
    @if($invoice->customer->user_id)
        <p><a href="{{ route('customer.invoices.show', $invoice) }}">View invoice in your portal</a></p>
    @else
        <p>Please contact the business if you need help accessing your invoice.</p>
    @endif
    <p>A PDF copy is attached.</p>
</body>
</html>
