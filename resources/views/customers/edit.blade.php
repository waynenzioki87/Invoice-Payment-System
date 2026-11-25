
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer | INVOICEX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <div class="card shadow-sm mx-auto" style="max-width: 700px;">
        <div class="card-header bg-warning">
            <h4 class="mb-0">Edit Customer</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('customers.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Customer Name</label>
                    <input type="text" name="name"
                           class="form-control"
                           value="{{ old('name', $customer->name) }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email"
                           class="form-control"
                           value="{{ old('email', $customer->email) }}">
                    @error('email')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone"
                           class="form-control"
                           value="{{ old('phone', $customer->phone) }}" required>
                    @error('phone')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"
                              rows="3">{{ old('address', $customer->address) }}</textarea>
                    @error('address')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('customers.index') }}"
                       class="btn btn-secondary">Cancel</a>

                    <button type="submit" class="btn btn-warning">
                        Update Customer
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>

</body>
</html>
