@extends('layouts.app')

@section('title', 'Leave Request Details')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Leave Request Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('leaves.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Leave Requests
            </a>
            @if($leave->status === 'pending')
                <a href="{{ route('leaves.edit', $leave->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Edit Request
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Leave Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Leave Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Leave Type</h6>
                            <p class="form-control-plaintext">
                                <span class="badge" style="background-color: {{ $leave->getLeaveTypeColor() ?? '#6c757d' }};">
                                    {{ ucfirst($leave->type) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Status</h6>
                            <p class="form-control-plaintext">
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
                                    @case('cancelled')
                                        <span class="badge bg-secondary">Cancelled</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ $leave->status }}</span>
                                @endswitch
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Start Date</h6>
                            <p class="form-control-plaintext">{{ $leave->start_date->format('F j, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">End Date</h6>
                            <p class="form-control-plaintext">{{ $leave->end_date->format('F j, Y') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">Number of Days</h6>
                            <p class="form-control-plaintext">{{ $leave->days }} days</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Applied On</h6>
                            <p class="form-control-plaintext">{{ $leave->created_at->format('F j, Y, g:i A') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h6 class="text-muted">Reason</h6>
                            <p class="form-control-plaintext">{{ $leave->reason }}</p>
                        </div>
                    </div>

                    @if($leave->remarks)
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="text-muted">Remarks</h6>
                                <p class="form-control-plaintext">{{ $leave->remarks }}</p>
                            </div>
                        </div>
                    @endif

                    @if($leave->approved_by)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">Approved By</h6>
                                <p class="form-control-plaintext">{{ $leave->approvedBy->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Approved On</h6>
                                <p class="form-control-plaintext">{{ $leave->updated_at->format('F j, Y, g:i A') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Approval History -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Approval History
                    </h5>
                </div>
                <div class="card-body">
                    @if($leave->approvals->count() > 0)
                        @foreach($leave->approvals as $approval)
                            <div class="mb-3 p-3 border rounded">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">{{ $approval->level_label }}</h6>
                                    @switch($approval->status)
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
                                            <span class="badge bg-secondary">{{ $approval->status }}</span>
                                    @endswitch
                                </div>
                                
                                @if($approval->approver)
                                    <p class="mb-1"><strong>Approver:</strong> {{ $approval->approver->name }}</p>
                                @endif
                                
                                @if($approval->approved_at)
                                    <p class="mb-1"><strong>Date:</strong> {{ $approval->approved_at->format('M d, Y g:i A') }}</p>
                                @endif
                                
                                @if($approval->comments)
                                    <p class="mb-0"><strong>Comments:</strong> {{ $approval->comments }}</p>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted">No approval history available yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    @if($leave->status === 'pending')
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Actions</h5>
                                <p class="text-muted mb-0">You can edit or cancel this leave request while it's pending.</p>
                            </div>
                            <div>
                                <a href="{{ route('leaves.edit', $leave->id) }}" class="btn btn-warning">
                                    <i class="fas fa-edit me-1"></i> Edit Request
                                </a>
                                <form method="POST" action="{{ route('leaves.destroy', $leave->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this leave request?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times me-1"></i> Cancel Request
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
