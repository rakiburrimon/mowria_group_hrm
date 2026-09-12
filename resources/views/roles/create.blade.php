@extends('layouts.admin')

@section('title', 'Add Role')
@section('page_title', 'Add Role')
@section('breadcrumb', 'Home / Roles & Permissions / Add')

@section('content')
    <form method="POST" action="{{ route('roles.store') }}">
        @csrf

        <div class="card mb-4" style="max-width:640px;">
            <div class="card-header">
                <i class="fas fa-user-shield me-2 text-primary"></i>Role Details
            </div>
            <div class="card-body">
                <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. manager, hr_lead" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Use lowercase with underscores, e.g. <code>team_lead</code>.</small>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-key me-2 text-primary"></i>Permissions</span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(true)">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">Clear</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($permissionGroups as $group => $permissions)
                        <div class="col-md-4">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>{{ ucwords(str_replace(['-', '_'], ' ', $group)) }}</strong>
                                    <button type="button" class="btn btn-sm btn-link p-0" onclick="toggleGroup('{{ $group }}')">toggle</button>
                                </div>
                                @foreach($permissions as $permission)
                                    <div class="form-check">
                                        <input class="form-check-input perm-{{ $group }}" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{ $permission->id }}"
                                            {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm-{{ $permission->id }}">{{ $permission->name }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> Create Role
            </button>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
function toggleAll(checked) {
    document.querySelectorAll('input[name="permissions[]"]').forEach(cb => cb.checked = checked);
}

function toggleGroup(group) {
    const boxes = document.querySelectorAll('.perm-' + group);
    const allChecked = [...boxes].every(cb => cb.checked);
    boxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endpush
