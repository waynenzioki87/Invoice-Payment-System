<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | INVOICEX</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container min-vh-100 d-flex justify-content-center align-items-center">

    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5"
         style="max-width: 480px; width: 100%;">

        <div class="text-center mb-4">

            <h2 class="fw-bold text-primary">INVOICEX</h2>

            <div class="mb-3">
                <span class="badge bg-primary-subtle text-primary px-3 py-2">
                    Account Security
                </span>
            </div>

            <h4 class="fw-bold">Verify Your Email</h4>

            <p class="text-muted mt-3">
                Thank you for registering with INVOICEX!
            </p>

            <p class="text-muted">
                Before getting started, please verify your email address
                by clicking the verification link we sent you.
            </p>

            <p class="fw-semibold text-dark">
                {{ auth()->user()->email }}
            </p>

        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success text-center">
                A new verification link has been sent successfully.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="btn btn-primary w-100 py-2">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
            @csrf

            <button type="submit" class="btn btn-outline-secondary btn-sm">
                Logout
            </button>
        </form>

        <hr class="my-4">

        <p class="text-center text-muted small mb-0">
            &copy; {{ date('Y') }} INVOICEX. All rights reserved.
        </p>

    </div>

</div>

</body>
</html>