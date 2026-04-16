@extends('layouts.app')

@section('title', 'Employee Profile - ' . $employee->full_name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Employee Profile</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Employees
                </a>
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Edit Employee
                </a>
            </div>
            <div class="btn-group">
                <button type="button" 
                        class="btn btn-outline-info" 
                        onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print Profile
                </button>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="row">
        <!-- Profile Image -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-image me-2"></i>Profile Photo
                    </h5>
                </div>
                <div class="card-body text-center">
                    @if($employee->profile_image)
                        <img src="{{ asset('storage/' . $employee->profile_image) }}" 
                             alt="{{ $employee->full_name }}" 
                             class="img-fluid rounded-circle mb-3" 
                             style="max-width: 200px; max-height: 200px; object-fit: cover;">
                    @else
                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                             style="width: 200px; height: 200px;">
                            <i class="fas fa-user fa-3x"></i>
                        </div>
                    @endif
                    
                    <div class="mt-3">
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#uploadImageModal">
                            <i class="fas fa-camera me-1"></i> Change Photo
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>Employee Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Employee ID</h6>
                            <p class="form-control-plaintext">{{ $employee->employee_id }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            @switch($employee->status)
                                @case('active')
                                    <span class="badge bg-success fs-6">Active</span>
                                    @break
                                @case('inactive')
                                    <span class="badge bg-warning fs-6">Inactive</span>
                                    @break
                                @case('terminated')
                                    <span class="badge bg-danger fs-6">Terminated</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary fs-6">{{ $employee->status }}</span>
                            @endswitch
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Full Name</h6>
                            <p class="form-control-plaintext">{{ $employee->full_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Email</h6>
                            <p class="form-control-plaintext">{{ $employee->email }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Phone</h6>
                            <p class="form-control-plaintext">{{ $employee->phone ?: 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Position</h6>
                            <p class="form-control-plaintext">{{ $employee->position }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Department</h6>
                            <p class="form-control-plaintext">
                                @if($employee->department)
                                    {{ $employee->department->name }}
                                @else
                                    <span class="text-muted">No Department</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Hire Date</h6>
                            <p class="form-control-plaintext">{{ $employee->hire_date->format('F j, Y') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Salary</h6>
                            <p class="form-control-plaintext">${{ number_format($employee->salary, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Created At</h6>
                            <p class="form-control-plaintext">{{ $employee->created_at->format('F j, Y, g:i A') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Updated At</h6>
                            <p class="form-control-plaintext">{{ $employee->updated_at->format('F j, Y, g:i A') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">User Account</h6>
                            <p class="form-control-plaintext">{{ $employee->user->email }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance & Leave Summary -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>Recent Attendance
                    </h5>
                </div>
                <div class="card-body">
                    @if($employee->attendances->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employee->attendances->take(10) as $attendance)
                                        <tr>
                                            <td>{{ $attendance->date->format('M d, Y') }}</td>
                                            <td>
                                                @switch($attendance->status)
                                                    @case('present')
                                                        <span class="badge bg-success">Present</span>
                                                        @break
                                                    @case('absent')
                                                        <span class="badge bg-danger">Absent</span>
                                                        @break
                                                    @case('late')
                                                        <span class="badge bg-warning">Late</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $attendance->status }}</span>
                                                @endswitch
                                            </td>
                                            <td>{{ $attendance->check_in ? $attendance->check_in->format('g:i A') : '-' }}</td>
                                            <td>{{ $attendance->check_out ? $attendance->check_out->format('g:i A') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted">No attendance records found</p>
                        @endif
                    </div>
                </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-times me-2"></i>Recent Leave Requests
                    </h5>
                </div>
                <div class="card-body">
                    @if($employee->leaves->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employee->leaves->take(10) as $leave)
                                        <tr>
                                            <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                            <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                            <td>{{ ucfirst($leave->type) }}</td>
                                            <td>{{ $leave->reason }}</td>
                                            <td>
                                                @switch($leave->status)
                                                    @case('pending')
                                                        <span class="badge bg-warning">Pending</span>
                                                        @break
                                                    @case('approved')
                                                        <span class="badge bg-success">Approved</span>
                                                        @break
                                                    @case('rejected')
                                                        <span class="badge bg-danger">Rejected</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $leave->status }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted">No leave requests found</p>
                        @endif
                    </div>
                </div>
        </div>
    </div>
</div>

<!-- Upload Image Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadImageModalLabel">
                    <i class="fas fa-camera me-2"></i>Upload Profile Image
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('employees.uploadProfileImage', $employee->id) }}" enctype="multipart/form-data" id="uploadImageForm">
                    @csrf
                    <div class="mb-3">
                        <label for="profile_image_upload" class="form-label">Choose Image</label>
                        <input type="file" 
                               name="profile_image" 
                               id="profile_image_upload" 
                               class="form-control" 
                               accept="image/*" 
                               required>
                        <div class="form-text text-muted">
                            Allowed formats: JPG, JPEG, PNG, GIF. Maximum size: 2MB
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('uploadImageForm');
    const modal = new bootstrap.Modal(document.getElementById('uploadImageModal'));
    
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(uploadForm);
            
            fetch('{{ route('employees.uploadProfileImage', $employee->id) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    modal.hide();
                    
                    // Reload page to show new image
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        <strong>Success!</strong> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('.container-fluid').prepend(alert);
                    
                    // Auto-remove alert after 5 seconds
                    setTimeout(() => {
                        alert.remove();
                    }, 5000);
                } else {
                    // Show error message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show';
                    alert.innerHTML = `
                        <strong>Error!</strong> ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    document.querySelector('.container-fluid').prepend(alert);
                    
                    // Auto-remove alert after 5 seconds
                    setTimeout(() => {
                        alert.remove();
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
});
</script>
@endsection
