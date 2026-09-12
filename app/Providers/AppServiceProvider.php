<?php

namespace App\Providers;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Leave;
use App\Policies\AttendancePolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LeavePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Global application service provider.
 *
 * Registers Eloquent model policies for role-based authorization.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Bindings and singletons can be added here when needed.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * This is where model policies are linked to their Eloquent models
     * so that calls like $this->authorize('view', $employee) resolve
     * to the correct policy class.
     */
    public function boot(): void
    {
        // Super admin users bypass all Gate and policy checks.
        // This is the single place where the super-admin override is defined.
        Gate::before(function ($user) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });

        // Register policies for the core HRM models
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Leave::class, LeavePolicy::class);
        Gate::policy(Attendance::class, AttendancePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
    }
}
