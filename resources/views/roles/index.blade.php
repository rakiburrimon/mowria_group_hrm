@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('page_title', 'Roles & Permissions')
@section('breadcrumb', 'Home / Roles & Permissions')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('roles.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Add Role
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-shield me-2 text-primary"></i>Roles
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
