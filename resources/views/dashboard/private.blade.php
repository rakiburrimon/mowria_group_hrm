@extends('layouts.admin')

@section('title', 'My Dashboard')
@section('page_title', 'My Dashboard')
@section('breadcrumb', 'Home / My Dashboard')

@section('content')
    <!-- Notifications -->
    @if(!empty($dashboardData['notifications']))
        @foreach($dashboardData['notifications'] as $notification)
            <div class="alert alert-{{ $notification['type'] === 'error' ? 'danger' : $notification['type'] }} alert-dismissible fade show" role="alert">
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
    @endif

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <!-- Profile -->
        <div class="col-md-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="user-avatar" style="width:56px;height:56px;font-size:1.3rem;">
                            {{ strtoupper(substr($dashboardData['user_info']['full_name'], 0, 1)) }}
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">{{ $dashboardData['user_info']['full_name'] }}</h6>
                            <div class="text-muted small">{{ $dashboardData['user_info']['position'] }}</div>
                            <div class="text-muted small">{{ $dashboardData['user_info']['department'] }}</div>
                        </div>
                    </div>
                    <div class="row text-center g-0 border-top pt-3">
                        <div class="col-4">
                            <div class="small text-muted">Employee ID</div>
                            <div class="fw-semibold">{{ $dashboardData['user_info']['employee_id'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Hire Date</div>
                            <div class="fw-semibold">{{ \Carbon\Carbon::parse($dashboardData['user_info']['hire_date'])->format('M d, Y') }}</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Status</div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ ucfirst($employee->status) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="col-md-6 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-header">
                    <i class="fas fa-clock me-2 text-primary"></i>Today's Attendance
                </div>
                <div class="card-body">
                    @if($dashboardData['attendance_summary']['today']['status'] === 'not_marked')
                        <div class="text-center py-3">
                            <i class="fas fa-clock fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-3">Attendance not marked yet</p>
                            <button class="btn btn-primary" onclick="markAttendance()">
                                <i class="fas fa-check me-1"></i> Mark Attendance
                            </button>
                        </div>
                    @else
                        <div class="text-center">
                            <span class="badge fs-6 px-3 py-2 mb-3 bg-{{ $dashboardData['attendance_summary']['today']['status'] === 'present' ? 'success' : ($dashboardData['attendance_summary']['today']['status'] === 'late' ? 'warning' : 'danger') }}">
                                {{ ucfirst($dashboardData['attendance_summary']['today']['status']) }}
                            </span>
                            <div class="row">
                                <div class="col-6">
                                    <div class="small text-muted">Check In</div>
                                    <div class="fw-semibold">{{ $dashboardData['attendance_summary']['today']['check_in'] ?? '—' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="small text-muted">Check Out</div>
                                    <div class="fw-semibold">{{ $dashboardData['attendance_summary']['today']['check_out'] ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Leave Balance -->
        <div class="col-md-12 col-xl-4">
            <div class="card stat-card h-100">
                <div class="card-header">
                    <i class="fas fa-calendar-alt me-2 text-primary"></i>Leave Balance
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="h2 mb-0 fw-bold">{{ $dashboardData['leave_balance']['remaining'] }}</div>
                        <div class="text-muted small">Days Remaining</div>
                    </div>
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $dashboardData['leave_balance']['usage_percentage'] }}%"></div>
                    </div>
                    <div class="row text-center small">
                        <div class="col-4">
                            <div class="text-muted">Total</div>
                            <div class="fw-semibold">{{ $dashboardData['leave_balance']['total'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Used</div>
                            <div class="fw-semibold">{{ $dashboardData['leave_balance']['used'] }}</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted">Pending</div>
                            <div class="fw-semibold">{{ $dashboardData['leave_balance']['pending'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-chart-line me-2 text-primary"></i>Attendance Trend (30 Days)
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>Leave Usage
                </div>
                <div class="card-body">
                    <canvas id="leaveChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly summary & activities -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-calendar-check me-2 text-primary"></i>This Month Summary
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-3">
                            <div class="h3 fw-bold text-success mb-0">{{ $dashboardData['attendance_summary']['this_month']['present'] }}</div>
                            <div class="small text-muted">Present</div>
                        </div>
                        <div class="col-3">
                            <div class="h3 fw-bold text-danger mb-0">{{ $dashboardData['attendance_summary']['this_month']['absent'] }}</div>
                            <div class="small text-muted">Absent</div>
                        </div>
                        <div class="col-3">
                            <div class="h3 fw-bold text-warning mb-0">{{ $dashboardData['attendance_summary']['this_month']['late'] }}</div>
                            <div class="small text-muted">Late</div>
                        </div>
                        <div class="col-3">
                            <div class="h3 fw-bold text-info mb-0">{{ $dashboardData['attendance_summary']['this_month']['attendance_rate'] }}%</div>
                            <div class="small text-muted">Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-history me-2 text-primary"></i>Recent Activities
                </div>
                <div class="card-body">
                    @if(!empty($dashboardData['recent_activities']))
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
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-history fa-2x mb-2"></i>
                            <p class="mb-0">No recent activities</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const attendanceChart = new Chart(document.getElementById('attendanceChart'), {
    type: 'line',
    data: {{ json_encode($dashboardData['charts']['attendance']) }},
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

const leaveChart = new Chart(document.getElementById('leaveChart'), {
    type: 'doughnut',
    data: {{ json_encode($dashboardData['charts']['leave_balance']) }},
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

function markAttendance() {
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
    top: 6px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #dee2e6;
}
</style>
@endpush
