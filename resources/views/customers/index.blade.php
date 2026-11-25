<x-app-layout>
    <div class="container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="section-heading mb-1">Customers</h1>
                <p class="section-description">Manage your registered customers.</p>
            </div>
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i> Add Customer
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

        <form method="GET" action="{{ route('customers.index') }}" class="row g-2 mb-3">
            <div class="col-md-5">
                <input type="search" name="search" value="{{ request('search') }}"
                       class="form-control" placeholder="Search name, email or phone">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Search</button>
                <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>

        <div class="card content-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td>{{ $customer->id }}</td>
                                <td class="fw-semibold">{{ $customer->name }}</td>
                                <td>{{ $customer->email ?? 'N/A' }}</td>
                                <td>{{ $customer->phone }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to delete this customer?')">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">No customers registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($customers->hasPages())
                <div class="card-footer">{{ $customers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
