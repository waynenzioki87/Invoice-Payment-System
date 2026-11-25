<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="section-heading mb-1">Invoices</h1>
                <p class="section-description">Manage all customer invoices.</p>
            </div>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-plus me-1"></i> Create Invoice
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="GET" action="{{ route('invoices.index') }}" class="row g-2 mb-3">
            <div class="col-lg-4">
                <input type="search" name="search" value="{{ request('search') }}"
                       class="form-control" placeholder="Invoice number or customer">
            </div>
            <div class="col-sm-4 col-lg-2">
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['unpaid', 'partial', 'paid', 'overdue', 'draft'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 col-lg-2">
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="Issue date from">
            </div>
            <div class="col-sm-4 col-lg-2">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="Issue date to">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Filter</button>
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>

        <div class="card content-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Customer</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Currency</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer->name }}</td>
                                <td>{{ $invoice->issue_date }}</td>
                                <td>{{ $invoice->due_date }}</td>
                                <td><span class="badge text-bg-light border">{{ $invoice->currency_code }}</span></td>
                                <td class="fw-semibold">
                                    {{ $invoice->currency_symbol }} {{ number_format($invoice->total, 2) }}
                                </td>
                                <td>
                                    @if($invoice->status === 'paid')
                                        <span class="badge text-bg-success">Paid</span>
                                    @elseif($invoice->due_date->isBefore(today()) && in_array($invoice->status, ['unpaid', 'partial', 'overdue'], true))
                                        <span class="badge text-bg-danger">Overdue</span>
                                    @else
                                        <span class="badge text-bg-warning">{{ ucfirst($invoice->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('invoices.show', $invoice->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Delete this invoice?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">No invoices have been created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($invoices->hasPages())
                <div class="card-footer">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
