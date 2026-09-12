@extends('layouts.admin')

@section('title', 'Leave Approval Panel')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Leave Approval Panel</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('dashboard.private') }}" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                </a>
                <a href="{{ route('leaves.index') }}" class="btn btn-info">
                    <i class="fas fa-list me-1"></i> My Leaves
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ \App\Models\Leave::where('status', 'pending')->count() }}</h4>
                            <p class="mb-0">Pending Requests</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">{{ \App\Models\Leave::where('status', 'approved')->count() }}</h4>
                            <p class="mb-0">Approved Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
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
                            <h4 class="mb-0">{{ \App\Models\Leave::where('status', 'rejected')->count() }}</h4>
                            <p class="mb-0">Rejected Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-times-circle fa-2x"></i>
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
                            <h4 class="mb-0">{{ \App\Models\Leave::whereDate('created_at', today())->count() }}</h4>
                            <p class="mb-0">New Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-plus fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" action="{{ route('leaves.approvalPanel') }}" class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Search & Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Department Filter -->
                        <div class="col-md-3">
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

                        <!-- Type Filter -->
                        <div class="col-md-3">
                            <label for="type" class="form-label">Leave Type</label>
                            <select name="type" id="type" class="form-select">
                                <option value="">All Types</option>
                                @foreach($leaveTypes as $type)
                                    <option value="{{ $type->code }}" {{ request('type') == $type->code ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" 
                                   class="form-control" 
                                   id="date_from" 
                                   name="date_from" 
                                   value="{{ request('date_from') }}">
                        </div>

                        <!-- Date To -->
                        <div class="col-md-3">
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
                            <a href="{{ route('leaves.approvalPanel') }}" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Pending Leave Requests -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-clock me-2"></i>Pending Leave Requests
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {{ $dataTable->table(['class' => 'table table-striped table-hover w-100']) }}
            </div>
        </div>
    </div>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Process Leave Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="approvalForm">
                    @csrf
                    <input type="hidden" name="leave_id" id="leaveId">
                    <input type="hidden" name="status" id="approvalStatus">
                    
                    <div class="mb-3">
                        <label for="level" class="form-label">Approval Level</label>
                        <select name="level" id="level" class="form-select" required>
                            <option value="1">Manager</option>
                            <option value="2">HR</option>
                            <option value="3">Director</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comments" class="form-label">Comments</label>
                        <textarea name="comments" 
                                  id="comments" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Add your comments here..."></textarea>
                        <small class="text-muted">Comments are required when rejecting a request.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" id="submitApproval">Submit</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{ $dataTable->scripts() }}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
    const approvalForm = document.getElementById('approvalForm');
    const submitBtn = document.getElementById('submitApproval');
    
    window.showApprovalModal = function(leaveId, status) {
        document.getElementById('leaveId').value = leaveId;
        document.getElementById('approvalStatus').value = status;
        
        // Update modal title and button based on status
        const modalTitle = document.getElementById('approvalModalLabel');
        if (status === 'approved') {
            modalTitle.innerHTML = '<i class="fas fa-check-circle me-2"></i>Approve Leave Request';
            submitBtn.className = 'btn btn-success';
            submitBtn.innerHTML = '<i class="fas fa-check me-1"></i> Approve';
        } else {
            modalTitle.innerHTML = '<i class="fas fa-times-circle me-2"></i>Reject Leave Request';
            submitBtn.className = 'btn btn-danger';
            submitBtn.innerHTML = '<i class="fas fa-times me-1"></i> Reject';
        }
        
        // Clear previous comments
        document.getElementById('comments').value = '';
        
        approvalModal.show();
    };
    
    // Handle form submission
    submitBtn.addEventListener('click', function() {
        const status = document.getElementById('approvalStatus').value;
        const comments = document.getElementById('comments').value;
        
        // Validate comments for rejection
        if (status === 'rejected' && !comments.trim()) {
            alert('Comments are required when rejecting a leave request.');
            return;
        }
        
        // Submit form via AJAX
        const formData = new FormData(approvalForm);
        const leaveId = document.getElementById('leaveId').value;
        
        fetch(`/leaves/${leaveId}/approve`, {
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
                approvalModal.hide();
                showToast('success', data.message);

                // Refresh the DataTable without a full page reload
                if (window.LaravelDataTables && window.LaravelDataTables['leave-approvals-table']) {
                    window.LaravelDataTables['leave-approvals-table'].ajax.reload();
                } else {
                    setTimeout(() => window.location.reload(), 1500);
                }
            } else {
                showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('error', 'An error occurred while processing the request.');
        });
    });
});
</script>
@endpush
@endsection
