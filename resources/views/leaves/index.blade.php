@extends('layouts.app')

@section('title', 'My Leave Requests')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">My Leave Requests</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('leaves.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Apply for Leave
                </a>
                <a href="{{ route('leaves.balance') }}" class="btn btn-info">
                    <i class="fas fa-balance-scale me-1"></i> Leave Balance
                </a>
            </div>
        </div>
    </div>

    <!-- Leave Balance Cards -->
    <div class="row mb-4">
        @foreach($leaveBalances as $balance)
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background-color: {{ $balance->leaveType->color_code ?? '#6c757d' }}; color: white;">
                        <h6 class="mb-0">{{ $balance->leaveType->name ?? $balance->leave_type }}</h6>
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <h4 class="text-primary">{{ $balance->remaining_days }}</h4>
                            <p class="text-muted mb-0">Days Remaining</p>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Total: {{ $balance->total_days }}</small>
                            <small class="text-muted">Used: {{ $balance->used_days }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Search and Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" action="{{ route('leaves.index') }}" class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Search & Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
                            <a href="{{ route('leaves.index') }}" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Leave Requests Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt me-2"></i>Leave History
                <span class="badge bg-primary ms-2">{{ $leaves->total() }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if($leaves->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Applied On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaves as $leave)
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: {{ $leave->getLeaveTypeColor() ?? '#6c757d' }};">
                                            {{ ucfirst($leave->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $leave->start_date->format('M d, Y') }}</td>
                                    <td>{{ $leave->end_date->format('M d, Y') }}</td>
                                    <td>{{ $leave->days }}</td>
                                    <td>
                                        <span class="text-truncate d-block" style="max-width: 200px;" title="{{ $leave->reason }}">
                                            {{ Str::limit($leave->reason, 30) }}
                                        </span>
                                    </td>
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
                                            @case('cancelled')
                                                <span class="badge bg-secondary">Cancelled</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $leave->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $leave->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('leaves.show', $leave->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($leave->status === 'pending')
                                                <a href="{{ route('leaves.edit', $leave->id) }}" 
                                                   class="btn btn-sm btn-outline-warning" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" 
                                                      action="{{ route('leaves.destroy', $leave->id) }}" 
                                                      onsubmit="return confirm('Are you sure you want to cancel this leave request?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div>
                        Showing {{ $leaves->firstItem() }} to {{ $leaves->lastItem() }} 
                        of {{ $leaves->total() }} entries
                    </div>
                    <div>
                        {{ $leaves->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No leave requests found</h5>
                    <p class="text-muted">
                        You haven't applied for any leave yet.
                        <a href="{{ route('leaves.create') }}" class="btn btn-primary">Apply for Leave</a>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
