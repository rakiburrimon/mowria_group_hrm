@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb', 'Home / Admin Dashboard')

@section('content')
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="dashboardFilters" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="period" class="form-label small text-muted mb-1">Time Period</label>
                    <select name="period" id="period" class="form-select" onchange="updateDashboard()">
                        @foreach($dashboardData['filters']['periods'] as $period)
                            <option value="{{ $period['value'] }}" {{ request()->get('period') == $period['value'] ? 'selected' : '' }}>
                                {{ $period['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="department" class="form-label small text-muted mb-1">Department</label>
                    <select name="department" id="department" class="form-select" onchange="updateDashboard()">
                        <option value="">All Departments</option>
                        @foreach($dashboardData['filters']['departments'] as $department)
                            <option value="{{ $department['id'] }}" {{ request()->get('department') == $department['id'] ? 'selected' : '' }}>
                                {{ $department['name'] }} ({{ $department['employee_count'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="year" class="form-label small text-muted mb-1">Year</label>
                    <select name="year" id="year" class="form-select" onchange="updateDashboard()">
                        <option value="">All Years</option>
                        @foreach($dashboardData['filters']['years'] as $year)
                            <option value="{{ $year['value'] }}" {{ request()->get('year') == $year['value'] ? 'selected' : '' }}>
                                {{ $year['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button type="button" class="btn btn-outline-secondary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-accent"><i class="fas fa-users"></i></div>
                    <div>
                        <div class="text-muted small">Total Employees</div>
                        <h3 class="mb-0 fw-bold" id="statTotalEmployees">{{ $dashboardData['total_employees'] }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-green"><i class="fas fa-user-check"></i></div>
                    <div class="flex-grow-1">
                        <div class="text-muted small">Active Employees</div>
                        <h3 class="mb-1 fw-bold" id="statActive">{{ $dashboardData['active_inactive_employees']['active'] }}</h3>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" id="statActiveBar" style="width: {{ $dashboardData['active_inactive_employees']['active_percentage'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-orange"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="text-muted small">Present Today</div>
                        <h3 class="mb-0 fw-bold" id="statPresent">{{ $dashboardData['attendance_summary']['today']['present'] }}</h3>
                        <small class="text-muted">{{ $dashboardData['attendance_summary']['today']['late'] }} late</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon bg-red"><i class="fas fa-plane-departure"></i></div>
                    <div>
                        <div class="text-muted small">Pending Leaves</div>
                        <h3 class="mb-0 fw-bold" id="statPendingLeaves">{{ $dashboardData['leave_statistics']['pending'] }}</h3>
                        <small class="text-muted">{{ $dashboardData['leave_statistics']['approved'] }} approved</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-chart-line me-2 text-primary"></i>Attendance Analytics</span>
                    <small class="text-muted">{{ $dashboardData['analytics']['date_range']['start'] }} &rarr; {{ $dashboardData['analytics']['date_range']['end'] }}</small>
                </div>
                <div class="card-body">
                    <canvas id="attendanceAnalyticsChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>Department Breakdown
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Leave statistics -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>Leave Statistics
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-3">
                            <div class="h3 fw-bold text-success mb-0">{{ $dashboardData['leave_statistics']['approved'] }}</div>
                            <div class="small text-muted">Approved</div>
                        </div>
                        <div class="col-3">
                            <div class="h3 fw-bold text-warning mb-0">{{ $dashboardData['leave_statistics']['pending'] }}</div>
                            <div class="small text-muted">Pending</div>
                        </div>
                        <div class="col-3">
                            <div class="h3 fw-bold text-danger mb-0">{{ $dashboardData['leave_statistics']['rejected'] }}</div>
                            <div class="small text-muted">Rejected</div>
                        </div>
                        <div class="col-3">
                            <div class="h3 fw-bold text-info mb-0">{{ $dashboardData['leave_statistics']['total_days'] }}</div>
                            <div class="small text-muted">Total Days</div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <span class="small text-muted d-block mb-2">Leave by type</span>
                        @foreach($dashboardData['leave_statistics']['by_type'] as $type => $count)
                            <span class="badge rounded-pill text-bg-light border me-1 mb-1">{{ ucfirst($type) }}: {{ $count }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Hiring trends -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-2 text-primary"></i>Hiring Trends
                </div>
                <div class="card-body">
                    <canvas id="hiringTrendsChart" height="160"></canvas>
                    <div class="d-flex gap-4 mt-3 small text-muted">
                        <span><strong class="text-dark">{{ $dashboardData['analytics']['hiring_trends']['summary']['total_hired'] }}</strong> total hired</span>
                        <span><strong class="text-dark">{{ $dashboardData['analytics']['hiring_trends']['summary']['avg_hiring_rate'] }}</strong> avg / month</span>
                        <span><strong class="text-dark">{{ $dashboardData['analytics']['hiring_trends']['summary']['peak_month'] }}</strong> peak</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let attendanceData = {{ json_encode($dashboardData['analytics']['attendance_analytics']) }};
let departmentData = {{ json_encode($dashboardData['analytics']['department_analytics']) }};
let hiringData = {{ json_encode($dashboardData['analytics']['hiring_trends']) }};

const attendanceChart = new Chart(document.getElementById('attendanceAnalyticsChart'), {
    type: 'line',
    data: attendanceData,
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

const departmentChart = new Chart(document.getElementById('departmentChart'), {
    type: 'doughnut',
    data: departmentData,
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

const hiringChart = new Chart(document.getElementById('hiringTrendsChart'), {
    type: 'bar',
    data: hiringData,
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

function updateDashboard() {
    const form = document.getElementById('dashboardFilters');
    const params = new URLSearchParams(new FormData(form));
    const url = new URL(window.location);
    url.search = params.toString();
    window.history.pushState({}, '', url);
    loadCharts();
}

function loadCharts() {
    const period = document.getElementById('period').value || 'month';
    const department = document.getElementById('department').value || '';
    const year = document.getElementById('year').value || '';

    fetch(`/api/dashboard/advanced?period=${period}&department=${department}&year=${year}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) return;

        attendanceData = data.data.analytics.attendance_analytics;
        departmentData = data.data.analytics.department_analytics;
        hiringData = data.data.analytics.hiring_trends;

        attendanceChart.data = attendanceData;
        attendanceChart.update();
        departmentChart.data = departmentData;
        departmentChart.update();
        hiringChart.data = hiringData;
        hiringChart.update();

        document.getElementById('statTotalEmployees').textContent = data.data.total_employees;
        document.getElementById('statActive').textContent = data.data.active_inactive_employees.active;
        document.getElementById('statActiveBar').style.width = data.data.active_inactive_employees.active_percentage + '%';
        document.getElementById('statPresent').textContent = data.data.attendance_summary.today.present;
        document.getElementById('statPendingLeaves').textContent = data.data.leave_statistics.pending;
    })
    .catch(error => console.error('Error loading dashboard:', error));
}

function refreshDashboard() {
    loadCharts();
}
</script>
@endpush
