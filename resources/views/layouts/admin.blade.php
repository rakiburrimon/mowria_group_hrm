<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Mowria Group HRM</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables (Bootstrap 5 skin) -->
    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css" rel="stylesheet">

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1a2332;
            --sidebar-hover: #242f42;
            --sidebar-active: #2f3d55;
            --sidebar-text: #a4b0be;
            --sidebar-text-active: #ffffff;
            --accent: #5b73e8;
            --accent-2: #34c38f;
            --content-bg: #f3f5f9;
        }

        body {
            background-color: var(--content-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        /* ============ SIDEBAR ============ */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            transition: transform .25s ease;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: 1.25rem 1.5rem;
            color: #fff;
            font-size: 1.15rem;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .sidebar-brand .brand-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), #7b8ff0);
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 0;
        }

        .sidebar-section {
            padding: .9rem 1.5rem .4rem;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #5f6b80;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: .65rem 1.5rem;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: .92rem;
            border-left: 3px solid transparent;
            transition: all .15s ease;
        }

        .sidebar-link i { width: 18px; text-align: center; }

        .sidebar-link:hover {
            background: var(--sidebar-hover);
            color: var(--sidebar-text-active);
        }

        .sidebar-link.active {
            background: var(--sidebar-active);
            color: var(--sidebar-text-active);
            border-left-color: var(--accent);
        }

        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid rgba(255,255,255,.06);
            font-size: .75rem;
            color: #5f6b80;
        }

        /* ============ MAIN WRAPPER ============ */
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin .25s ease;
        }

        /* ============ TOPBAR ============ */
        .admin-topbar {
            background: #fff;
            padding: .85rem 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 1px 4px rgba(15,23,42,.06);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .topbar-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .topbar-breadcrumb {
            font-size: .8rem;
            color: #98a0ac;
        }

        .sidebar-toggle {
            border: none;
            background: transparent;
            font-size: 1.1rem;
            color: #5f6b80;
            display: none;
        }

        .topbar-user {
            margin-left: auto;
        }

        .topbar-user .dropdown-toggle {
            display: flex;
            align-items: center;
            gap: .6rem;
            color: #3b4557;
            text-decoration: none;
        }

        .topbar-user .dropdown-toggle::after { display: none; }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #7b8ff0);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 600;
            font-size: .85rem;
        }

        /* ============ CONTENT ============ */
        .admin-content {
            flex: 1;
            padding: 1.75rem;
        }

        /* ============ CARDS ============ */
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(15,23,42,.05);
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(15,23,42,.1);
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.35rem;
            color: #fff;
        }

        .stat-icon.bg-accent { background: linear-gradient(135deg, #5b73e8, #7b8ff0); }
        .stat-icon.bg-green { background: linear-gradient(135deg, #34c38f, #46d6a2); }
        .stat-icon.bg-orange { background: linear-gradient(135deg, #f1b44c, #f5c86e); }
        .stat-icon.bg-red { background: linear-gradient(135deg, #f46a6a, #f78a8a); }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(15,23,42,.05);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #eef0f4;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
        }

        /* ============ MOBILE ============ */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,.5);
            z-index: 1035;
            display: none;
        }

        @media (max-width: 991.98px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.show { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .sidebar-toggle { display: block; }
            .sidebar-backdrop.show { display: block; }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar backdrop (mobile) -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="sidebar-brand" href="{{ route('dashboard.private') }}">
            <span class="brand-icon"><i class="fas fa-building"></i></span>
            <span>Mowria HRM</span>
        </a>

        @php
            $navUser = Auth::user();
            $isAdmin = $navUser && $navUser->hasAnyRole(['admin', 'super_admin']);
        @endphp
        <nav class="sidebar-nav">
            <div class="sidebar-section">Main</div>
            <a class="sidebar-link {{ request()->routeIs('dashboard.private') ? 'active' : '' }}" href="{{ route('dashboard.private') }}">
                <i class="fas fa-home"></i> My Dashboard
            </a>
            @if($isAdmin)
                <a class="sidebar-link {{ request()->routeIs('dashboard.admin') ? 'active' : '' }}" href="{{ route('dashboard.admin') }}">
                    <i class="fas fa-th-large"></i> Admin Dashboard
                </a>
            @endif
            <a class="sidebar-link {{ request()->routeIs('leaves.index') ? 'active' : '' }}" href="{{ route('leaves.index') }}">
                <i class="fas fa-plane-departure"></i> My Leaves
            </a>
            <a class="sidebar-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}" href="{{ route('attendance.index') }}">
                <i class="fas fa-clock"></i> Attendance
            </a>

            @if($isAdmin)
                <div class="sidebar-section">Management</div>
                <a class="sidebar-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                    <i class="fas fa-users"></i> Employees
                </a>
                <a class="sidebar-link {{ request()->routeIs('leaves.approvalPanel') ? 'active' : '' }}" href="{{ route('leaves.approvalPanel') }}">
                    <i class="fas fa-check-double"></i> Approvals
                </a>
                <a class="sidebar-link {{ request()->routeIs('attendance.reports') ? 'active' : '' }}" href="{{ route('attendance.reports') }}">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>

                <div class="sidebar-section">System</div>
                <a class="sidebar-link" href="#">
                    <i class="fas fa-building"></i> Departments
                </a>
                <a class="sidebar-link" href="#">
                    <i class="fas fa-user-shield"></i> Roles &amp; Permissions
                </a>
                <a class="sidebar-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" href="{{ route('activity-logs.index') }}">
                    <i class="fas fa-history"></i> Activity Log
                </a>
                <a class="sidebar-link" href="#">
                    <i class="fas fa-cog"></i> Settings
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            &copy; {{ date('Y') }} Mowria Group HRM
        </div>
    </aside>

    <!-- Main -->
    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <button class="sidebar-toggle" id="sidebarToggle" type="button">
                <i class="fas fa-bars"></i>
            </button>

            <div>
                <h1 class="topbar-title">@yield('page_title', 'Dashboard')</h1>
                <div class="topbar-breadcrumb">@yield('breadcrumb', 'Dashboard')</div>
            </div>

            <div class="topbar-user dropdown">
                <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <span class="user-avatar">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</span>
                    <span class="d-none d-md-inline">
                        <div class="fw-semibold" style="line-height:1.1">{{ Auth::user()->name }}</div>
                        <small class="text-muted">{{ Auth::user()->getRoleNames()->first() ?? 'user' }}</small>
                    </span>
                    <i class="fas fa-chevron-down small ms-1"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <!-- Content -->
        <main class="admin-content">
            @yield('content')
        </main>
    </div>

    <!-- Toast notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer" style="z-index: 2000;"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery + DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script>
        // Sidebar toggle for mobile
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });
        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });

        /**
         * Show a toast notification.
         *
         * @param {string} type    success | error | danger | warning | info | primary
         * @param {string} message Message to display
         * @param {number} delay   Auto-hide delay in ms (0 = sticky)
         */
        function showToast(type, message, delay = 4000) {
            const styles = {
                success: { cls: 'text-bg-success', icon: 'fa-check-circle' },
                error:   { cls: 'text-bg-danger',  icon: 'fa-times-circle' },
                danger:  { cls: 'text-bg-danger',  icon: 'fa-times-circle' },
                warning: { cls: 'text-bg-warning', icon: 'fa-exclamation-triangle' },
                info:    { cls: 'text-bg-info',    icon: 'fa-info-circle' },
            };
            const style = styles[type] || { cls: 'text-bg-primary', icon: 'fa-bell' };

            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center ${style.cls} border-0`;
            toastEl.setAttribute('role', 'alert');
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body"><i class="fas ${style.icon} me-2"></i>${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>`;

            document.getElementById('toastContainer').appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl, { delay });
            toast.show();
            toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
        }

        // Render Laravel flash messages as toasts
        @foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info', 'status' => 'info'] as $key => $type)
            @if (session($key))
                showToast('{{ $type }}', @json(session($key)));
            @endif
        @endforeach

        // Render validation / error bag as toasts
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                showToast('error', @json($error));
            @endforeach
        @endif
    </script>

    @stack('scripts')
</body>
</html>
