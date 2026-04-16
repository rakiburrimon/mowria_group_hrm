@extends('layouts.app')

@section('title', 'Advanced Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Advanced Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Filters & Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <form id="dashboardFilters" class="row g-3">
                        <!-- Period Filter -->
                        <div class="col-md-3">
                            <label for="period" class="form-label">Time Period</label>
                            <select name="period" id="period" class="form-select" onchange="updateDashboard()">
                                @foreach($dashboardData['filters']['periods'] as $period)
                                    <option value="{{ $period['value'] }}" {{ request()->get('period') == $period['value'] ? 'selected' : '' }}>
                                        {{ $period['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Department Filter -->
                        <div class="col-md-3">
                            <label for="department" class="form-label">Department</label>
                            <select name="department" id="department" class="form-select" onchange="updateDashboard()">
                                <option value="">All Departments</option>
                                @foreach($dashboardData['filters']['departments'] as $department)
                                    <option value="{{ $department['id'] }}" {{ request()->get('department') == $department['id'] ? 'selected' : '' }}>
                                        {{ $department['name'] }} ({{ $department['employee_count'] }} employees)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Year Filter -->
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <select name="year" id="year" class="form-select" onchange="updateDashboard()">
                                <option value="">All Years</option>
                                @foreach($dashboardData['filters']['years'] as $year)
                                    <option value="{{ $year['value'] }}" {{ request()->get('year') == $year['value'] ? 'selected' : '' }}>
                                        {{ $year['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview Cards -->
    <div class="row mb-4">
        <!-- Total Employees -->
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $dashboardData['total_employees'] }}</h3>
                    <div class="small">Total Employees</div>
                </div>
            </div>
        </div>

        <!-- Active vs Inactive -->
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3 class="mb-1">{{ $dashboardData['active_inactive_employees']['active'] }}</h3>
                    <div class="small">Active</div>
                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $dashboardData['active_inactive_employees']['active_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h4 class="mb-1">Attendance</h4>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="small text-muted">Present</div>
                            <div class="fw-bold">{{ $dashboardData['attendance_summary']['today']['present'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Absent</div>
                            <div class="fw-bold">{{ $dashboardData['attendance_summary']['today']['absent'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Late</div>
                            <div class="fw-bold">{{ $dashboardData['attendance_summary']['today']['late'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Attendance Analytics Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Attendance Analytics
                        <small class="text-muted">{{ $dashboardData['analytics']['period'] }} - {{ $dashboardData['analytics']['date_range']['start'] }} to {{ $dashboardData['analytics']['date_range']['end'] }}</small>
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceAnalyticsChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <!-- Department Analytics Chart -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Department Breakdown
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Statistics & Hiring Trends -->
    <div class="row mb-4">
        <!-- Leave Statistics -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Leave Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="h4 text-success">{{ $dashboardData['leave_statistics']['approved'] }}</div>
                            <div class="small text-muted">Approved</div>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-warning">{{ $dashboardData['leave_statistics']['pending'] }}</div>
                            <div class="small text-muted">Pending</div>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-danger">{{ $dashboardData['leave_statistics']['rejected'] }}</div>
                            <div class="small text-muted">Rejected</div>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-info">{{ $dashboardData['leave_statistics']['total_days'] }}</div>
                            <div class="small text-muted">Total Days</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Leave by Type:</strong>
                            @foreach($dashboardData['leave_statistics']['by_type'] as $type => $count)
                                <span class="badge bg-secondary me-1">{{ ucfirst($type) }}: {{ $count }}</span>
                            @endforeach
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hiring Trends -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Hiring Trends
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="hiringTrendsChart" height="200"></canvas>
                    <div class="mt-3">
                        <div class="small text-muted">
                            <strong>Total Hired:</strong> {{ $dashboardData['hiring_trends']['summary']['total_hired'] }}
                        </div>
                        <div class="small text-muted">
                            <strong>Avg Monthly:</strong> {{ $dashboardData['hiring_trends']['summary']['avg_hiring_rate'] }}
                        </div>
                        <div class="small text-muted">
                            <strong>Peak Month:</strong> {{ $dashboardData['hiring_trends']['summary']['peak_month'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Global chart data
let attendanceData = {{ json_encode($dashboardData['analytics']['attendance_analytics']) }};
let departmentData = {{ json_encode($dashboardData['analytics']['department_analytics']) }};
let hiringData = {{ json_encode($dashboardData['analytics']['hiring_trends']) }};

// Attendance Analytics Chart
const attendanceCtx = document.getElementById('attendanceAnalyticsChart').getContext('2d');
const attendanceChart = new Chart(attendanceCtx, {
    type: 'line',
    data: attendanceData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Department Chart
const departmentCtx = document.getElementById('departmentChart').getContext('2d');
const departmentChart = new Chart(departmentCtx, {
    type: 'doughnut',
    data: departmentData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        }
    }
});

// Hiring Trends Chart
const hiringCtx = document.getElementById('hiringTrendsChart').getContext('2d');
const hiringChart = new Chart(hiringCtx, {
    type: 'bar',
    data: hiringData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'bottom'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Update dashboard function
function updateDashboard() {
    const form = document.getElementById('dashboardFilters');
    const formData = new FormData(form);
    
    const params = new URLSearchParams(formData);
    const url = new URL(window.location);
    
    // Update URL without page reload
    url.search = params.toString();
    window.history.pushState({}, '', url);
    
    // Reload charts with new data
    loadCharts();
}

// Load charts with current filters
function loadCharts() {
    const period = document.getElementById('period').value || 'month';
    const department = document.getElementById('department').value || '';
    const year = document.getElementById('year').value || '';
    
    // Make AJAX request to get updated data
    fetch(`/api/dashboard/advanced?period=${period}&department=${department}&year=${year}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update global chart data
            attendanceData = data.data.analytics.attendance_analytics;
            departmentData = data.data.analytics.department_analytics;
            hiringData = data.data.analytics.hiring_trends;
            
            // Update charts
            attendanceChart.data = attendanceData;
            attendanceChart.update();
            
            departmentChart.data = departmentData;
            departmentChart.update();
            
            hiringChart.data = hiringData;
            hiringChart.update();
            
            // Update stats cards
            updateStatsCards(data.data);
        } else {
            console.error('Failed to load dashboard data:', data.message);
        }
    })
    .catch(error => console.error('Error loading dashboard:', error));
}

// Update statistics cards
function updateStatsCards(data) {
    // Update total employees
    const totalEmployeesCard = document.querySelector('.bg-primary .h3');
    if (totalEmployeesCard) {
        totalEmployeesCard.textContent = data.total_employees;
    }
    
    // Update active/inactive stats
    const activeCard = document.querySelector('.bg-success .h3');
    const activeProgressBar = document.querySelector('.bg-success .progress-bar');
    if (activeCard && activeProgressBar) {
        activeCard.textContent = data.active_inactive_employees.active;
        activeProgressBar.style.width = data.active_inactive_employees.active_percentage + '%';
    }
}

// Refresh dashboard
function refreshDashboard() {
    loadCharts();
}

// Initialize charts on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCharts();
});
</script>

<style>
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 10px 10px 0 0;
    font-weight: 600;
}

.progress {
    background-color: #e9ecef;
    border-radius: 5px;
}

.form-select {
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.bg-primary, .bg-success, .bg-info {
    transition: transform 0.2s;
}

.bg-primary:hover, .bg-success:hover, .bg-info:hover {
    transform: translateY(-2px);
}
</style>
@endpush
