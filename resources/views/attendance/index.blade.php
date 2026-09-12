@extends('layouts.admin')

@section('title', 'Attendance Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Attendance Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('attendance.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Attendance
                </a>
                <a href="{{ route('attendance.monthly') }}" class="btn btn-info">
                    <i class="fas fa-calendar me-1"></i> Monthly View
                </a>
                <a href="{{ route('attendance.reports') }}" class="btn btn-success">
                    <i class="fas fa-chart-bar me-1"></i> Reports
                </a>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-success" onclick="checkIn()">
                    <i class="fas fa-sign-in-alt me-1"></i> Check In
                </button>
                <button type="button" class="btn btn-outline-danger" onclick="checkOut()">
                    <i class="fas fa-sign-out-alt me-1"></i> Check Out
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0" id="todayPresentCount">{{ $stats['today_present'] }}</h4>
                            <p class="mb-0">Present Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0" id="todayAbsentCount">{{ $stats['today_absent'] }}</h4>
                            <p class="mb-0">Absent Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-user-times fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0" id="todayLateCount">{{ $stats['today_late'] }}</h4>
                            <p class="mb-0">Late Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ $stats['month_present'] + $stats['month_absent'] + $stats['month_late'] }}</h4>
                            <p class="mb-0">Records This Month</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" action="{{ route('attendance.index') }}" class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Search & Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Employee Filter -->
                        <div class="col-md-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select name="employee_id" id="employee_id" class="form-select">
                                <option value="">All Employees</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" 
                                            {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->full_name }} ({{ $employee->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Department Filter -->
                        <div class="col-md-2">
                            <label for="department_id" class="form-label">Department</label>
                            <select name="department_id" id="department_id" class="form-select">
                                <option value="">All Departments</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" 
                                            {{ request('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>On Leave</option>
                                <option value="holiday" {{ request('status') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_from" 
                                   name="date_from" 
                                   value="{{ request('date_from') }}">
                        </div>

                        <!-- Date To -->
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_to" 
                                   name="date_to" 
                                   value="{{ request('date_to') }}">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-search me-1"></i> Apply Filters
                            </button>
                            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Records Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Attendance Records
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {{ $dataTable->table(['class' => 'table table-striped table-hover w-100']) }}
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
{{ $dataTable->scripts() }}
<script>
function checkIn() {
    fetch('{{ route('attendance.checkIn') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            refreshAttendance();
        } else {
            showToast('error', data.message);
        }
    })
    .catch(() => showToast('error', 'An error occurred while checking in.'));
}

function checkOut() {
    fetch('{{ route('attendance.checkOut') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message);
            refreshAttendance();
        } else {
            showToast('error', data.message);
        }
    })
    .catch(() => showToast('error', 'An error occurred while checking out.'));
}

function refreshAttendance() {
    // Reload the DataTable rows
    if (window.LaravelDataTables && window.LaravelDataTables['attendances-table']) {
        window.LaravelDataTables['attendances-table'].ajax.reload(null, false);
    }

    // Refresh the stat cards
    fetch('{{ route('attendance.statistics') }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('todayPresentCount').textContent = data.data.today_present;
            document.getElementById('todayAbsentCount').textContent = data.data.today_absent;
            document.getElementById('todayLateCount').textContent = data.data.today_late;
        }
    })
    .catch(() => {});
}
</script>
@endpush
