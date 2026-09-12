@extends('layouts.admin')

@section('title', 'Dashboard Error')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow border-warning">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Dashboard Error
                </h4>
            </div>
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="fas fa-exclamation-circle fa-3x text-warning mb-3"></i>
                </div>
                <h5>Unable to Load Dashboard</h5>
                <p class="text-muted">
                    @if(isset($error))
                        {{ $error }}
                    @else
                        An unexpected error occurred while loading the dashboard.
                    @endif
                </p>
                <div class="mt-3">
                    <button onclick="history.back()" class="btn btn-warning">
                        <i class="fas fa-arrow-left me-2"></i>
                        Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
