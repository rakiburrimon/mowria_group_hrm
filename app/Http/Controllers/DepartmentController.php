<?php

namespace App\Http\Controllers;

use App\DataTables\DepartmentsDataTable;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use App\Traits\HandlesServiceExceptions;

class DepartmentController extends Controller
{
    use HandlesServiceExceptions;

    public function __construct(
        protected DepartmentService $departments
    ) {}

    /**
     * Display a listing of departments.
     */
    public function index(DepartmentsDataTable $dataTable)
    {
        $this->authorize('viewAny', Department::class);

        return $dataTable->render('departments.index');
    }

    /**
     * Show the form for creating a department.
     */
    public function create()
    {
        $this->authorize('create', Department::class);

        return view('departments.create');
    }

    /**
     * Store a newly created department.
     */
    public function store(StoreDepartmentRequest $request)
    {
        $this->authorize('create', Department::class);

        $data = $request->validated();

        return $this->handleService(function () use ($data) {
            $this->departments->create($data);

            return redirect()->route('departments.index')
                ->with('success', 'Department created successfully.');
        }, 'Failed to create department');
    }

    /**
     * Show the form for editing a department.
     */
    public function edit(Department $department)
    {
        $this->authorize('update', $department);

        return view('departments.edit', compact('department'));
    }

    /**
     * Update the specified department.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $this->authorize('update', $department);

        $data = $request->validated();

        return $this->handleService(function () use ($department, $data) {
            $this->departments->update($department, $data);

            return redirect()->route('departments.index')
                ->with('success', 'Department updated successfully.');
        }, 'Failed to update department');
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        $this->authorize('delete', $department);

        return $this->handleService(function () use ($department) {
            if ($department->employees()->exists()) {
                return redirect()->route('departments.index')
                    ->with('error', 'Cannot delete a department that has employees.');
            }

            $this->departments->delete($department);

            return redirect()->route('departments.index')
                ->with('success', 'Department deleted successfully.');
        }, 'Failed to delete department');
    }
}
