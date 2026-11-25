<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>INVOICEX | Invoice Management System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            overflow-y: auto;
            background: linear-gradient(180deg, #1682df 0%, #0965bd 100%);
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 15px;
            color: white;
            z-index: 1050;
        }

        .brand {
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: block;
            padding: 0 12px 30px;
        }

        .sidebar-label {
            color: rgba(255, 255, 255, .72);
            font-size: 11px;
            text-transform: uppercase;
            margin: 20px 12px 12px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, .9);
            text-decoration: none;
            padding: 12px;
            margin-bottom: 6px;
            border-radius: 7px;
            font-size: 14px;
            transition: .2s;
        }

        .menu-link:hover,
        .menu-link.active {
            background: #fff;
            color: #1374ca;
        }

        .menu-link.disabled {
            opacity: .55;
            cursor: not-allowed;
        }

        .menu-link i {
            font-size: 17px;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }

        .topbar {
            min-height: 75px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,.04);
        }

        .page-content {
            padding: 30px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .role-badge {
            font-size: 10px;
            padding: 4px 9px;
            border-radius: 20px;
            text-transform: uppercase;
            background: #dcfce7;
            color: #166534;
        }

        .logout-button {
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, .9);
            width: 100%;
            text-align: left;
            padding: 12px;
            font-size: 14px;
        }

        .logout-button:hover {
            color: white;
        }

        .section-heading {
            color: #202a3c;
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: -.03em;
        }

        .section-description {
            color: #7a8497;
            margin-bottom: 0;
        }

        .content-card {
            overflow: hidden;
            border: 1px solid #e8ebf1;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(27, 39, 61, .05);
        }

        .content-card .table {
            margin-bottom: 0;
        }

        .content-card .table thead th {
            padding: 14px 16px;
            color: #657087;
            background: #f8f9fb;
            border-bottom: 1px solid #e8ebf1;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .content-card .table tbody td {
            padding: 14px 16px;
            color: #354057;
            border-color: #edf0f4;
            vertical-align: middle;
        }

        .content-card .card-footer {
            background: #fff;
            border-top: 1px solid #edf0f4;
        }

        .sidebar-overlay {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
                transition: transform .3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 1040;
            }

            .main-content {
                margin-left: 0;
            }

            .page-content {
                padding: 15px;
            }

            .topbar {
                padding: 12px 15px;
            }

            .section-heading {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

@auth
    @php
        $user = auth()->user();
        $role = $user->role;

        $dashboardRoute = match ($role) {
            'admin' => 'admin.dashboard',
            'staff' => 'staff.dashboard',
            default => 'customer.dashboard',
        };

        $menus = [];

        if ($role === 'admin') {
            $menus = [
                'Main Menu' => [
                    ['Dashboard', 'bi-grid', 'admin.dashboard'],
                    ['Customers', 'bi-people', 'customers.index'],
                    ['Invoices', 'bi-file-earmark-text', 'invoices.index'],
                    ['Payments', 'bi-wallet2', 'payments.index'],
                    ['Reports', 'bi-bar-chart', 'reports.index'],
                ],
                'Administration' => [
                    ['User Management', 'bi-person-gear', 'admin.users.index'],
                    ['Business Settings', 'bi-gear', 'settings.index'],
                    ['Audit Logs', 'bi-shield-check', 'admin.audit-logs.index'],
                ],
            ];
        } elseif ($role === 'staff') {
            $menus = [
                'Main Menu' => [
                    ['Dashboard', 'bi-grid', 'staff.dashboard'],
                    ['Customers', 'bi-people', 'customers.index'],
                    ['Invoices', 'bi-file-earmark-text', 'invoices.index'],
                    ['Payments', 'bi-wallet2', 'payments.index'],
                    ['Reports', 'bi-bar-chart', 'reports.index'],
                ],
            ];
        } else {
            $menus = [
                'Main Menu' => [
                    ['My Dashboard', 'bi-grid', 'customer.dashboard'],
                    ['My Invoices', 'bi-file-earmark-text', 'customer.invoices.index'],
                    ['Payments', 'bi-wallet2', 'customer.payments.index'],
                    ['My Receipts', 'bi-receipt', 'customer.receipts.index'],
                ],
            ];
        }
    @endphp

    <div class="sidebar-overlay" id="sidebarOverlay"
         onclick="closeSidebar()"></div>

    <aside class="sidebar" id="sidebar">

        <a href="{{ route($dashboardRoute) }}" class="brand">
            <i class="bi bi-receipt"></i> INVOICEX
        </a>

        @foreach($menus as $section => $items)

            <p class="sidebar-label">{{ $section }}</p>

            @foreach($items as $item)
                @php
                    [$label, $icon, $routeName] = $item;
                    $routeExists = \Illuminate\Support\Facades\Route::has($routeName);
                @endphp

                @if($routeExists)
                    <a href="{{ route($routeName) }}"
                       class="menu-link {{ request()->routeIs($routeName) ? 'active' : '' }}">
                        <i class="bi {{ $icon }}"></i>
                        {{ $label }}
                    </a>
                @else
                    <span class="menu-link disabled"
                          title="This module has not been connected yet">
                        <i class="bi {{ $icon }}"></i>
                        {{ $label }}
                    </span>
                @endif
            @endforeach

        @endforeach

        <p class="sidebar-label mt-4">Account</p>

        <a href="{{ route('profile.edit') }}"
           class="menu-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <i class="bi bi-person-circle"></i>
            My Profile
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="logout-button">
                <i class="bi bi-box-arrow-right me-2"></i>
                Logout
            </button>
        </form>

    </aside>

    <div class="main-content">

        <header class="topbar">

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-md-none"
                        type="button"
                        onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>

                <h6 class="mb-0 fw-semibold">
                    Invoice Management System
                </h6>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-semibold small">{{ $user->name }}</div>
                    <span class="role-badge">{{ $role }}</span>
                </div>

                <div class="user-avatar">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            </div>

        </header>

        <main class="page-content">
            {{ $slot }}
        </main>

    </div>

@else
    {{ $slot }}
@endauth

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('show');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('show');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
