<x-app-layout>

<div class="container-fluid">

    <!-- Welcome -->
    <div class="mb-4">
        <h3 class="fw-bold">Admin Dashboard</h3>
        <p class="text-muted mb-0">
            Welcome back, {{ auth()->user()->name }}. Here's your business overview.
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Invoices</p>
                            <h3 class="fw-bold">{{ $totalInvoices }}</h3>
                        </div>
                        <i class="bi bi-receipt fs-1 text-primary"></i>
                    </div>
                    <small class="text-muted">All recorded invoices</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Overdue Invoices</p>
                            <h3 class="fw-bold text-danger">{{ $overdueInvoices }}</h3>
                        </div>
                        <i class="bi bi-exclamation-circle fs-1 text-danger"></i>
                    </div>
                    <small class="text-muted">Past due with a balance</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Customers</p>
                            <h3 class="fw-bold">{{ $totalCustomers }}</h3>
                        </div>
                        <i class="bi bi-people fs-1 text-success"></i>
                    </div>
                    <small class="text-muted">Registered customers</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Paid Invoices</p>
                            <h3 class="fw-bold text-success">{{ $paidInvoices }}</h3>
                        </div>
                        <i class="bi bi-check-circle fs-1 text-success"></i>
                    </div>
                    <small class="text-muted">Invoices marked as paid</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Unpaid Invoices</p>
                            <h3 class="fw-bold text-warning">{{ $unpaidInvoices }}</h3>
                        </div>
                        <i class="bi bi-clock-history fs-1 text-warning"></i>
                    </div>
                    <small class="text-muted">Awaiting payment</small>
                </div>
            </div>
        </div>

    </div>

    <!-- Invoice Progress -->
    <div class="row g-3 mb-4">

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Invoice Progress</h5>

                    @php
                        $paidPercentage = $totalInvoices > 0
                            ? ($paidInvoices / $totalInvoices) * 100
                            : 0;

                        $unpaidPercentage = $totalInvoices > 0
                            ? ($unpaidInvoices / $totalInvoices) * 100
                            : 0;
                    @endphp

                    <div class="d-flex justify-content-between mb-2">
                        <span>Paid invoices</span>
                        <strong>{{ number_format($paidPercentage, 1) }}%</strong>
                    </div>

                    <div class="progress mb-4" style="height: 12px;">
                        <div class="progress-bar bg-success"
                             style="width: {{ $paidPercentage }}%"></div>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Unpaid invoices</span>
                        <strong>{{ number_format($unpaidPercentage, 1) }}%</strong>
                    </div>

                    <div class="progress mb-4" style="height: 12px;">
                        <div class="progress-bar bg-warning"
                             style="width: {{ $unpaidPercentage }}%"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Overdue invoices</span>
                        <strong class="text-danger">{{ $overdueInvoices }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Currency Summary -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Financial Overview</h5>

                    @forelse($currencyTotals as $currency)
                        <div class="border-bottom py-3">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>{{ $currency->currency_code }}</strong>
                                <span class="text-muted">Invoice totals</span>
                            </div>

                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Total invoiced</small>
                                <strong>
                                    {{ number_format($currency->total_invoiced, 2) }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between">
                                <small class="text-success">Payments received</small>
                                <strong class="text-success">
                                    {{ number_format($currency->paid_total, 2) }}
                                </strong>
                            </div>

                            <div class="d-flex justify-content-between">
                                <small class="text-warning">Outstanding</small>
                                <strong class="text-warning">
                                    {{ number_format($currency->outstanding_total, 2) }}
                                </strong>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">No financial records available yet.</p>
                    @endforelse

                    <small class="text-muted">
                        Amounts are displayed separately by currency.
                    </small>
                </div>
            </div>
        </div>

    </div>

    <!-- Monthly Chart -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Monthly Invoice Activity</h5>
            <div style="height: 300px;">
                <canvas id="invoiceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Recent Invoices</h5>

                @if(\Illuminate\Support\Facades\Route::has('invoices.index'))
                    <a href="{{ route('invoices.index') }}"
                       class="btn btn-sm btn-primary">
                        View All
                    </a>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Currency</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>{{ $invoice->invoice_number }}</td>

                                <td>
                                    {{ $invoice->customer->name ?? 'Unknown' }}
                                </td>

                                <td>
                                    {{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M Y') }}
                                </td>

                                <td>{{ $invoice->currency_code }}</td>

                                <td>
                                    {{ $invoice->currency_symbol }}
                                    {{ number_format($invoice->total, 2) }}
                                </td>

                                <td>
                                    @if($invoice->status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($invoice->status === 'unpaid')
                                        <span class="badge bg-warning text-dark">Unpaid</span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No invoices have been created yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const monthlyData = @json($monthlyInvoices);

    const monthNames = [
        'January', 'February', 'March', 'April',
        'May', 'June', 'July', 'August',
        'September', 'October', 'November', 'December'
    ];

    const invoiceCounts = Array(12).fill(0);

    monthlyData.forEach(item => {
        invoiceCounts[item.month - 1] = item.total;
    });

    new Chart(document.getElementById('invoiceChart'), {
        type: 'bar',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'Invoices Created',
                data: invoiceCounts,
                backgroundColor: '#2563eb',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
</script>

</x-app-layout>
