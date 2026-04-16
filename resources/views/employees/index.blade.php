@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Employees Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('employees.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Add Employee
                </a>
                <a href="{{ route('employees.statistics') }}" class="btn btn-info">
                    <i class="fas fa-chart-bar me-1"></i> Statistics
                </a>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-3">
        <div class="col-12">
            <form method="GET" action="{{ route('employees.index') }}" class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Search & Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Search -->
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Search by name, email, ID, position...">
                        </div>

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

                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                            </select>
                        </div>

                        <!-- Position Filter -->
                        <div class="col-md-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="position" 
                                   name="position" 
                                   value="{{ request('position') }}" 
                                   placeholder="Filter by position...">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select name="sort_by" id="sort_by" class="form-select">
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Created Date</option>
                                <option value="first_name" {{ request('sort_by') == 'first_name' ? 'selected' : '' }}>First Name</option>
                                <option value="last_name" {{ request('sort_by') == 'last_name' ? 'selected' : '' }}>Last Name</option>
                                <option value="employee_id" {{ request('sort_by') == 'employee_id' ? 'selected' : '' }}>Employee ID</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="sort_order" class="form-label">Order</label>
                            <select name="sort_order" id="sort_order" class="form-select">
                                <option value="desc" {{ request('sort_order') == 'desc' ? 'selected' : '' }}>Descending</option>
                                <option value="asc" {{ request('sort_order') == 'asc' ? 'selected' : '' }}>Ascending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-search me-1"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users me-2"></i>Employees List
                <span class="badge bg-primary ms-2">{{ $employees->total() }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if($employees->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Photo</th>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Hire Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                                <tr>
                                    <td>
                                        @if($employee->profile_image)
                                            <img src="{{ asset('storage/' . $employee->profile_image) }}" 
                                                 alt="{{ $employee->full_name }}" 
                                                 class="rounded-circle" 
                                                 style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $employee->employee_id }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('employees.show', $employee->id) }}" class="text-decoration-none">
                                            {{ $employee->full_name }}
                                        </a>
                                    </td>
                                    <td>{{ $employee->email }}</td>
                                    <td>
                                        @if($employee->department)
                                            <span class="badge bg-info">{{ $employee->department->name }}</span>
                                        @else
                                            <span class="text-muted">No Department</span>
                                        @endif
                                    </td>
                                    <td>{{ $employee->position }}</td>
                                    <td>
                                        @switch($employee->status)
                                            @case('active')
                                                <span class="badge bg-success">Active</span>
                                                @break
                                            @case('inactive')
                                                <span class="badge bg-warning">Inactive</span>
                                                @break
                                            @case('terminated')
                                                <span class="badge bg-danger">Terminated</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $employee->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $employee->hire_date->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('employees.show', $employee->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('employees.edit', $employee->id) }}" 
                                               class="btn btn-sm btn-outline-warning" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" 
                                                  action="{{ route('employees.destroy', $employee->id) }}" 
                                                  onsubmit="return confirm('Are you sure you want to delete this employee?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
                        Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} 
                        of {{ $employees->total() }} entries
                    </div>
                    <div>
                        {{ $employees->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No employees found</h5>
                    <p class="text-muted">
                        Try adjusting your search criteria or 
                        <a href="{{ route('employees.create') }}" class="btn btn-primary">add a new employee</a>
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
