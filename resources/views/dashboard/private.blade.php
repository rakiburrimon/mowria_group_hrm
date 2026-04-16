@extends('layouts.app')

@section('title', 'Private Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    @if(!empty($dashboardData['notifications']))
        <div class="row mb-4">
            <div class="col-12">
                @foreach($dashboardData['notifications'] as $notification)
                    <div class="alert alert-{{ $notification['type'] }} alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-{{ $notification['icon'] }} me-2"></i>
                            <div class="flex-grow-1">
                                <strong>{{ $notification['title'] }}</strong>
                                <div class="small">{{ $notification['message'] }}</div>
                            </div>
                            @if(isset($notification['action_url']))
                                <a href="{{ $notification['action_url'] }}" class="btn btn-sm btn-outline-secondary ms-2">
                                    Take Action
                                </a>
                            @endif
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- User Overview Card -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Overview</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">{{ $dashboardData['user_info']['full_name'] }}</h6>
                            <div class="text-muted small">{{ $dashboardData['user_info']['position'] }}</div>
                            <div class="text-muted small">{{ $dashboardData['user_info']['department'] }}</div>
                        </div>
                    </div>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="small text-muted">Employee ID</div>
                            <div class="fw-bold">{{ $dashboardData['user_info']['employee_id'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Hire Date</div>
                            <div class="fw-bold">{{ \Carbon\Carbon::parse($dashboardData['user_info']['hire_date'])->format('M d, Y') }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Status</div>
                            <span class="badge bg-success">{{ ucfirst($employee->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Today's Attendance</h5>
                </div>
                <div class="card-body">
                    @if($dashboardData['attendance_summary']['today']['status'] === 'not_marked')
                        <div class="text-center py-3">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Attendance not marked yet</p>
                            <button class="btn btn-info" onclick="markAttendance()">Mark Attendance</button>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="mb-3">
                                <span class="badge bg-{{ $dashboardData['attendance_summary']['today']['status'] === 'present' ? 'success' : ($dashboardData['attendance_summary']['today']['status'] === 'late' ? 'warning' : 'danger') }} badge-lg">
                                    {{ ucfirst($dashboardData['attendance_summary']['today']['status']) }}
                                </span>
                            </div>
                            @if($dashboardData['attendance_summary']['today']['check_in'])
                                <div class="small text-muted">Check In</div>
                                <div class="fw-bold">{{ $dashboardData['attendance_summary']['today']['check_in'] }}</div>
                            @endif
                            @if($dashboardData['attendance_summary']['today']['check_out'])
                                <div class="small text-muted mt-2">Check Out</div>
                                <div class="fw-bold">{{ $dashboardData['attendance_summary']['today']['check_out'] }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leave Balance -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Leave Balance</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="h2 mb-0">{{ $dashboardData['leave_balance']['remaining'] }}</div>
                        <div class="text-muted">Days Remaining</div>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" style="width: {{ $dashboardData['leave_balance']['usage_percentage'] }}%"></div>
                    </div>
                    <div class="row text-center small">
                        <div class="col-4">
                            <div class="text-muted">Total</div>
                            <div class="fw-bold">{{ $dashboardData['leave_balance']['total'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Used</div>
                            <div class="fw-bold">{{ $dashboardData['leave_balance']['used'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Pending</div>
                            <div class="fw-bold">{{ $dashboardData['leave_balance']['pending'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Attendance Chart -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Attendance Trend (30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Leave Usage Chart -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Leave Usage</h5>
                </div>
                <div class="card-body">
                    <canvas id="leaveChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Summary & Recent Activities -->
    <div class="row">
        <!-- Monthly Attendance Summary -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>This Month Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="h4 text-success mb-0">{{ $dashboardData['attendance_summary']['this_month']['present'] }}</div>
                            <div class="small text-muted">Present</div>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-danger mb-0">{{ $dashboardData['attendance_summary']['this_month']['absent'] }}</div>
                            <div class="small text-muted">Absent</div>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-warning mb-0">{{ $dashboardData['attendance_summary']['this_month']['late'] }}</div>
                            <div class="small text-muted">Late</div>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-info mb-0">{{ $dashboardData['attendance_summary']['this_month']['attendance_rate'] }}%</div>
                            <div class="small text-muted">Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activities</h5>
                </div>
                <div class="card-body">
                    @if(!empty($dashboardData['recent_activities']))
                        <div class="timeline">
                            @foreach($dashboardData['recent_activities'] as $activity)
                                <div class="timeline-item mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="me-3">
                                            @if($activity['type'] === 'attendance')
                                                <i class="fas fa-clock text-info"></i>
                                            @elseif($activity['type'] === 'leave')
                                                <i class="fas fa-calendar text-warning"></i>
                                            @else
                                                <i class="fas fa-circle text-muted"></i>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small">{{ $activity['description'] }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                {{ \Carbon\Carbon::parse($activity['date'])->format('M d, Y') }}
                                                @if(isset($activity['time']))
                                                    at {{ $activity['time'] }}
                                                @endif
                                                @if(isset($activity['duration']))
                                                    • {{ $activity['duration'] }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-history fa-2x mb-2"></i>
                            <p>No recent activities</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Attendance Chart
const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
const attendanceData = {{ json_encode($dashboardData['charts']['attendance']) }};
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

// Leave Chart
const leaveCtx = document.getElementById('leaveChart').getContext('2d');
const leaveData = {{ json_encode($dashboardData['charts']['leave_balance']) }};
const leaveChart = new Chart(leaveCtx, {
    type: 'doughnut',
    data: leaveData,
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

// Refresh Dashboard
function refreshDashboard() {
    window.location.reload();
}

// Mark Attendance (placeholder function)
function markAttendance() {
    // This would typically open a modal or navigate to attendance page
    alert('Attendance marking feature would be implemented here');
}
</script>

<style>
.timeline-item {
    border-left: 2px solid #e9ecef;
    padding-left: 20px;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 8px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #dee2e6;
}

.badge-lg {
    font-size: 1rem;
    padding: 0.5rem 1rem;
}
</style>
@endpush
