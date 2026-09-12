@extends('layouts.admin')

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
function cancelLeave(id, url) {
    if (!confirm('Are you sure you want to cancel this leave request?')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    form.innerHTML = '@csrf<input type="hidden" name="_method" value="DELETE">';
    document.body.appendChild(form);
    form.submit();
}
</script>
@endpush
