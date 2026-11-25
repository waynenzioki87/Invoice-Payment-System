
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details | INVOICEX</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <div class="card shadow-sm mx-auto" style="max-width: 700px;">

        <div class="card-header bg-info text-white">
            <h4 class="mb-0">Customer Details</h4>
        </div>

        <div class="card-body">

            <div class="mb-3">
                <label class="text-muted">Customer ID</label>
                <h5>{{ $customer->id }}</h5>
            </div>

            <hr>

            <div class="mb-3">
                <label class="text-muted">Full Name</label>
                <h5>{{ $customer->name }}</h5>
            </div>

            <div class="mb-3">
                <label class="text-muted">Email Address</label>
                <h5>{{ $customer->email ?? 'Not provided' }}</h5>
            </div>

            <div class="mb-3">
                <label class="text-muted">Phone Number</label>
                <h5>{{ $customer->phone }}</h5>
            </div>

            <div class="mb-3">
                <label class="text-muted">Address</label>
                <h5>{{ $customer->address ?? 'Not provided' }}</h5>
            </div>

            <div class="mb-3">
                <label class="text-muted">Date Registered</label>
                <h5>{{ $customer->created_at->format('d M Y') }}</h5>
            </div>

            <hr>

            <div class="d-flex justify-content-between">

                <a href="{{ route('customers.index') }}"
                   class="btn btn-secondary">
                    Back to Customers
                </a>

                <a href="{{ route('customers.edit', $customer->id) }}"
                   class="btn btn-warning">
                    Edit Customer
                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>
