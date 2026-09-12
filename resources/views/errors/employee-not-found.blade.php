@extends('layouts.admin')

@section('title', 'Employee Profile Not Found')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Employee Profile Not Found
                </h4>
            </div>
            <div class="card-body text-center p-5">
                <div class="mb-4">
                    <i class="fas fa-user-slash fa-4x text-warning"></i>
                </div>
                
                <h5 class="mb-3">No Employee Profile Associated</h5>
                
                <p class="text-muted mb-4">
                    Your user account exists, but we couldn't find an associated employee profile in the system. 
                    This might happen if:
                </p>
                
                <ul class="text-start text-muted mb-4">
                    <li>Your employee profile hasn't been created yet</li>
                    <li>Your account is not properly linked to an employee record</li>
                    <li>Your employee status is inactive</li>
                </ul>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Next Steps:</strong> Please contact your HR department or system administrator 
                    to resolve this issue and get your employee profile set up.
                </div>
                
                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('login') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Login
                    </a>
                    <button onclick="history.back()" class="btn btn-outline-primary">
                        <i class="fas fa-redo me-2"></i>
                        Try Again
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
