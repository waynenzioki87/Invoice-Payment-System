<!DOCTYPE html>
<html lang="en">
<body style="font-family:Arial,sans-serif;color:#243047;line-height:1.6">
    <h1>Payment received</h1>
    <p>Hello {{ $payment->invoice->customer->name }},</p>
    <p>We received your payment for invoice <strong>{{ $payment->invoice->invoice_number }}</strong>.</p>
    <p>
        Amount: {{ $payment->invoice->currency_code }} {{ number_format($payment->amount, 2) }}<br>
        Date: {{ $payment->payment_date?->format('d M Y') ?? 'N/A' }}<br>
        Reference: {{ $payment->transaction_reference ?: 'N/A' }}
    </p>
    @if($payment->invoice->customer->user_id)
        <p><a href="{{ route('customer.payments.show', $payment) }}">View payment details</a></p>
    @endif
    <p>Your PDF receipt is attached.</p>
</body>
</html>
