@extends('layouts.admin')

@section('title', 'Edit Employee - ' . $employee->full_name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Employee</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Profile
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Edit Employee Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('employees.update', $employee->id) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- User Selection -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="user_id" class="form-label">User Account *</label>
                                <select name="user_id" id="user_id" class="form-select @error('user_id') ? 'is-invalid' : ''" required>
                                    <option value="">Select a user</option>
                                    @foreach(App\Models\User::all() as $user)
                                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->email }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="department_id" class="form-label">Department *</label>
                                <select name="department_id" id="department_id" class="form-select @error('department_id') ? 'is-invalid' : ''" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status *</label>
                                <select name="status" id="status" class="form-select @error('status') ? 'is-invalid' : ''" required>
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" {{ old('status') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="employee_id" class="form-label">Employee ID *</label>
                                <input type="text" 
                                       name="employee_id" 
                                       id="employee_id" 
                                       class="form-control @error('employee_id') ? 'is-invalid' : ''" 
                                       value="{{ old('employee_id', $employee->employee_id) }}" 
                                       placeholder="Enter unique employee ID" 
                                       required>
                                @error('employee_id')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="position" class="form-label">Position *</label>
                                <input type="text" 
                                       name="position" 
                                       id="position" 
                                       class="form-control @error('position') ? 'is-invalid' : ''" 
                                       value="{{ old('position', $employee->position) }}" 
                                       placeholder="Enter job position" 
                                       required>
                                @error('position')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" 
                                       name="first_name" 
                                       id="first_name" 
                                       class="form-control @error('first_name') ? 'is-invalid' : ''" 
                                       value="{{ old('first_name', $employee->first_name) }}" 
                                       placeholder="Enter first name" 
                                       required>
                                @error('first_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" 
                                       name="last_name" 
                                       id="last_name" 
                                       class="form-control @error('last_name') ? 'is-invalid' : ''" 
                                       value="{{ old('last_name', $employee->last_name) }}" 
                                       placeholder="Enter last name" 
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control @error('email') ? 'is-invalid' : ''" 
                                       value="{{ old('email', $employee->email) }}" 
                                       placeholder="Enter email address" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" 
                                       name="phone" 
                                       id="phone" 
                                       class="form-control @error('phone') ? 'is-invalid' : ''" 
                                       value="{{ old('phone', $employee->phone) }}" 
                                       placeholder="Enter phone number">
                                @error('phone')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="hire_date" class="form-label">Hire Date *</label>
                                <input type="date" 
                                       name="hire_date" 
                                       id="hire_date" 
                                       class="form-control @error('hire_date') ? 'is-invalid' : ''" 
                                       value="{{ old('hire_date', $employee->hire_date->format('Y-m-d')) }}" 
                                       required>
                                @error('hire_date')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="salary" class="form-label">Salary</label>
                                <input type="number" 
                                       name="salary" 
                                       id="salary" 
                                       class="form-control @error('salary') ? 'is-invalid' : ''" 
                                       value="{{ old('salary', $employee->salary) }}" 
                                       placeholder="Enter salary" 
                                       step="0.01" 
                                       min="0">
                                @error('salary')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <!-- Current Profile Image -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Current Profile Image</label>
                                @if($employee->profile_image)
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ asset('storage/' . $employee->profile_image) }}" 
                                             alt="{{ $employee->full_name }}" 
                                             class="rounded-circle" 
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                        <div>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="confirmAction('Remove Profile Image', 'Remove the current profile image? The change applies when you save.', 'Yes, Remove', function() {
                                                        document.getElementById('remove_current_image').value = '1';
                                                        showToast('info', 'Profile image will be removed when you save.');
                                                    })">
                                                <i class="fas fa-trash me-1"></i> Remove Current
                                            </button>
                                            <input type="hidden" name="remove_current_image" id="remove_current_image" value="0">
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted">No profile image currently uploaded</p>
                                @endif
                            </div>
                        </div>

                        <!-- New Profile Image -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="profile_image" class="form-label">New Profile Image</label>
                                <input type="file" 
                                       name="profile_image" 
                                       id="profile_image" 
                                       class="form-control @error('profile_image') ? 'is-invalid' : ''" 
                                       accept="image/*">
                                @error('profile_image')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <div class="form-text text-muted">
                                    Leave empty to keep current image. Allowed formats: JPG, JPEG, PNG, GIF. Maximum size: 2MB
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('employees.show', $employee->id) }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Employee
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
@endsection
