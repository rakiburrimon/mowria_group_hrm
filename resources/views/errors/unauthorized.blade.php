@extends('layouts.app')

@section('title', 'Unauthorized Access')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow border-danger">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Access Denied
                </h4>
            </div>
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                </div>
                <h5>Unauthorized Access</h5>
                <p class="text-muted">
                    You don't have permission to access the Advanced Dashboard.
                    Please contact your system administrator if you believe this is an error.
                </p>
                <div class="mt-3">
                    <a href="{{ route('login') }}" class="btn btn-danger">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
