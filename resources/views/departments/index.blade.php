@extends('layouts.admin')

@section('title', 'Departments')
@section('page_title', 'Departments')
@section('breadcrumb', 'Home / Departments')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <form method="GET" action="{{ route('departments.index') }}" class="d-flex gap-2">
                <select name="status" class="form-select" style="width:180px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </form>
        </div>
        <a href="{{ route('departments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Department
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-building me-2 text-primary"></i>Departments
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {{ $dataTable->table(['class' => 'table table-striped table-hover w-100']) }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
{{ $dataTable->scripts() }}
@endpush
