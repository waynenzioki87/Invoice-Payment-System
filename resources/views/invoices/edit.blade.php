
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice | INVOICEX</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">

    <h2 class="mb-2">Edit Invoice</h2>
    <p class="text-muted mb-4">Update invoice information.</p>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <form action="{{ route('invoices.update', $invoice->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Customer -->
                <div class="mb-3">
                    <label class="form-label">Customer</label>

                    <select name="customer_id" class="form-select" required>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ old('customer_id', $invoice->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('customer_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Invoice Number -->
                <div class="mb-3">
                    <label class="form-label">Invoice Number</label>
                    <input type="text" class="form-control"
                        value="{{ $invoice->invoice_number }}" disabled>
                    <small class="text-muted">Invoice numbers cannot be changed.</small>
                </div>

                <!-- Currency -->
                <div class="mb-3">
                    <label class="form-label">Invoice Currency</label>

                    <select name="currency_code" class="form-select" required>
                        @foreach($currencies as $code => $name)
                            <option value="{{ $code }}"
                                {{ old('currency_code', $invoice->currency_code) == $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>

                    @error('currency_code')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror

                    <small class="text-muted">
                        Changing the currency changes how the existing invoice amounts are labelled; it does not convert them.
                    </small>
                </div>

                <!-- Dates -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Issue Date</label>
                        <input type="date" name="issue_date"
                            class="form-control"
                            value="{{ old('issue_date', $invoice->issue_date) }}" required>

                        @error('issue_date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date"
                            class="form-control"
                            value="{{ old('due_date', $invoice->due_date) }}" required>

                        @error('due_date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Notes -->
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $invoice->notes) }}</textarea>

                    @error('notes')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Invoice</button>

                <a href="{{ route('invoices.index') }}" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>

</div>

</body>
</html>
