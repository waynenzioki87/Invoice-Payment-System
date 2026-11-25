
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer | INVOICEX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <div class="card shadow-sm mx-auto" style="max-width: 700px;">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Add New Customer</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('customers.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Customer Name</label>
                    <input type="text" name="name"
                           class="form-control"
                           value="{{ old('name') }}"
                           placeholder="Enter customer name" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email"
                           class="form-control"
                           value="{{ old('email') }}"
                           placeholder="Enter email address">
                    @error('email')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone"
                           class="form-control"
                           value="{{ old('phone') }}"
                           placeholder="e.g. 0712345678" required>
                    @error('phone')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"
                              rows="3"
                              placeholder="Enter customer address">{{ old('address') }}</textarea>
                    @error('address')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('customers.index') }}"
                       class="btn btn-secondary">Cancel</a>

                    <button type="submit" class="btn btn-primary">
                        Save Customer
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>
