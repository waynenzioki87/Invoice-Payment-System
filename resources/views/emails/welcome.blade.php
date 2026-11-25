<!DOCTYPE html>
<html lang="en">
<body style="font-family:Arial,sans-serif;color:#243047;line-height:1.6">
    <h1>Welcome to INVOICEX, {{ $user->name }}</h1>
    <p>Your customer portal account is ready. Sign in to view your invoices, balances, and payment history.</p>
    <p><a href="{{ route('login') }}">Sign in to INVOICEX</a></p>
</body>
</html>
