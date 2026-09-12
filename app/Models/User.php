<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Traits\LogsModelActivity;
use Spatie\Permission\Traits\HasRoles;

// Define mass-assignable and hidden attributes using Eloquent attributes
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsModelActivity;

    /**
     * Role constant for the system-wide super administrator.
     * Super admins bypass all permission checks via Gate::before.
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Role constant for administrators / HR managers.
     * Admins are granted the bulk of management permissions.
     */
    public const ROLE_ADMIN = 'admin';

    /**
     * Role constant for regular employees.
     * Employees have limited, self-focused permissions.
     */
    public const ROLE_EMPLOYEE = 'employee';

    /**
     * Relationship to the user's employee profile.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
