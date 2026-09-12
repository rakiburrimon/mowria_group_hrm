@extends('layouts.admin')

@section('title', 'Apply for Leave')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Apply for Leave</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('leaves.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Leave Requests
            </a>
        </div>
    </div>

    <!-- Leave Balance Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-balance-scale me-2"></i>Your Leave Balance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($leaveBalances as $balance)
                            <div class="col-md-3 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <h6 class="text-primary">{{ $balance->leaveType->name ?? $balance->leave_type }}</h6>
                                        <h3 class="text-primary">{{ $balance->remaining_days }}</h3>
                                        <p class="text-muted mb-0">Days Available</p>
                                        <small class="text-muted">Total: {{ $balance->total_days }} | Used: {{ $balance->used_days }}</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Application Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>Leave Application
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('leaves.store') }}" id="leaveForm">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Leave Type *</label>
                                <select name="type" id="type" class="form-select @error('type') ? 'is-invalid' : ''" required>
                                    <option value="">Select Leave Type</option>
                                    @foreach($leaveTypes as $type)
                                        <option value="{{ $type->code }}" 
                                                data-max-days="{{ $type->max_days_per_year }}"
                                                data-requires-approval="{{ $type->requires_approval ? 'true' : 'false' }}"
                                                {{ old('type') == $type->code ? 'selected' : '' }}>
                                            {{ $type->name }} ({{ $type->max_days_per_year }} days/year)
                                        </option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="days" class="form-label">Number of Days *</label>
                                <input type="number" 
                                       name="days" 
                                       id="days" 
                                       class="form-control @error('days') ? 'is-invalid' : ''" 
                                       value="{{ old('days') }}" 
                                       min="0.5" 
                                       step="0.5" 
                                       required>
                                @error('days')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" 
                                       name="start_date" 
                                       id="start_date" 
                                       class="form-control @error('start_date') ? 'is-invalid' : ''" 
                                       value="{{ old('start_date') }}" 
                                       required>
                                @error('start_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label">End Date *</label>
                                <input type="date" 
                                       name="end_date" 
                                       id="end_date" 
                                       class="form-control @error('end_date') ? 'is-invalid' : ''" 
                                       value="{{ old('end_date') }}" 
                                       required>
                                @error('end_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="reason" class="form-label">Reason *</label>
                                <textarea name="reason" 
                                          id="reason" 
                                          class="form-control @error('reason') ? 'is-invalid' : ''" 
                                          rows="4" 
                                          placeholder="Please provide a reason for your leave request..."
                                          required>{{ old('reason') }}</textarea>
                                @error('reason')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Balance Check Warning -->
                        <div id="balanceWarning" class="alert alert-warning d-none">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> You may not have enough leave days for this request. Please check your leave balance.
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('leaves.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-paper-plane me-1"></i> Submit Leave Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const daysInput = document.getElementById('days');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const balanceWarning = document.getElementById('balanceWarning');
    const submitBtn = document.getElementById('submitBtn');
    const leaveForm = document.getElementById('leaveForm');

    // Leave balances data
    const leaveBalances = @json($leaveBalances);

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    startDateInput.min = today;
    endDateInput.min = today;

    // Auto-calculate days when dates change
    function calculateDays() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);
        
        if (startDateInput.value && endDateInput.value && endDate >= startDate) {
            const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
            daysInput.value = days;
            checkBalance();
        }
    }

    // Check leave balance
    function checkBalance() {
        const selectedType = typeSelect.value;
        const requestedDays = parseFloat(daysInput.value) || 0;
        
        if (selectedType && requestedDays > 0) {
            const balance = leaveBalances.find(b => b.leave_type === selectedType);
            
            if (balance && requestedDays > balance.remaining_days) {
                balanceWarning.classList.remove('d-none');
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-danger');
                submitBtn.classList.remove('btn-primary');
            } else {
                balanceWarning.classList.add('d-none');
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-danger');
                submitBtn.classList.add('btn-primary');
            }
        }
    }

    // Event listeners
    startDateInput.addEventListener('change', function() {
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
        calculateDays();
    });

    endDateInput.addEventListener('change', calculateDays);
    typeSelect.addEventListener('change', checkBalance);
    daysInput.addEventListener('input', checkBalance);

    // Form submission
    leaveForm.addEventListener('submit', function(e) {
        if (submitBtn.disabled) {
            e.preventDefault();
            alert('Please check your leave balance before submitting.');
            return false;
        }
    });
});
</script>
@endsection
