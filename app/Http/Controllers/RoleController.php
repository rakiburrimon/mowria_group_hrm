<?php

namespace App\Http\Controllers;

use App\DataTables\RolesDataTable;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Traits\HandlesServiceExceptions;
use App\Traits\LogsActions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use HandlesServiceExceptions;
    use LogsActions;

    /**
     * Display a listing of roles.
     */
    public function index(RolesDataTable $dataTable)
    {
        return $dataTable->render('roles.index');
    }

    /**
     * Show the form for creating a role.
     */
    public function create()
    {
        $permissionGroups = $this->permissionGroups();

        return view('roles.create', compact('permissionGroups'));
    }

    /**
     * Store a newly created role with its permissions.
     */
    public function store(StoreRoleRequest $request)
    {
        $data = $request->validated();

        return $this->handleService(function () use ($data) {
            $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
            $role->syncPermissions($data['permissions'] ?? []);

            $this->logAction('role created', $role, [
                'permissions' => $data['permissions'] ?? [],
            ]);

            return redirect()->route('roles.index')
                ->with('success', 'Role created successfully.');
        }, 'Failed to create role');
    }

    /**
     * Show the form for editing a role's permissions.
     */
    public function edit(Role $role)
    {
        $permissionGroups = $this->permissionGroups();
        $rolePermissions = $role->permissions->pluck('name')->all();

        return view('roles.edit', compact('role', 'permissionGroups', 'rolePermissions'));
    }

    /**
     * Update a role's name and permissions.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $data = $request->validated();

        return $this->handleService(function () use ($role, $data) {
            // Protect the super_admin role slug
            if ($role->name === 'super_admin') {
                $role->syncPermissions(Permission::pluck('name')->all());

                return redirect()->route('roles.index')
                    ->with('warning', 'The super_admin role always keeps all permissions.');
            }

            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);

            $this->logAction('role updated', $role, [
                'permissions' => $data['permissions'] ?? [],
            ]);

            return redirect()->route('roles.index')
                ->with('success', 'Role updated successfully.');
        }, 'Failed to update role');
    }

    /**
     * Remove a role.
     */
    public function destroy(Role $role)
    {
        return $this->handleService(function () use ($role) {
            if ($role->name === 'super_admin') {
                return redirect()->route('roles.index')
                    ->with('error', 'The super_admin role cannot be deleted.');
            }

            if ($role->users()->exists()) {
                return redirect()->route('roles.index')
                    ->with('error', 'Cannot delete a role that is assigned to users.');
            }

            $this->logAction('role deleted', $role);
            $role->delete();

            return redirect()->route('roles.index')
                ->with('success', 'Role deleted successfully.');
        }, 'Failed to delete role');
    }

    /**
     * Permissions grouped by their module prefix for the checkbox UI.
     */
    private function permissionGroups(): \Illuminate\Support\Collection
    {
        return Permission::orderBy('name')->get()
            ->groupBy(fn (Permission $p) => explode('.', $p->name)[0]);
    }
}
