<?php

namespace App\DataTables;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class EmployeesDataTable extends DataTable
{
    /**
     * Build the DataTable response (columns formatting).
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('photo', function (Employee $employee) {
                if ($employee->profile_image) {
                    return '<img src="' . asset('storage/' . $employee->profile_image) . '"
                            alt="' . e($employee->full_name) . '"
                            class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">';
                }

                return '<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width:40px;height:40px;"><i class="fas fa-user"></i></div>';
            })
            ->editColumn('full_name', function (Employee $employee) {
                return '<a href="' . route('employees.show', $employee->id) . '" class="text-decoration-none">'
                    . e($employee->full_name) . '</a>';
            })
            ->addColumn('department', function (Employee $employee) {
                return $employee->department
                    ? '<span class="badge bg-info">' . e($employee->department->name) . '</span>'
                    : '<span class="text-muted">No Department</span>';
            })
            ->editColumn('employee_id', fn (Employee $employee) => '<span class="badge bg-secondary">' . e($employee->employee_id) . '</span>')
            ->editColumn('status', fn (Employee $employee) => $this->statusBadge($employee->status))
            ->editColumn('hire_date', fn (Employee $employee) => $employee->hire_date?->format('M d, Y'))
            ->addColumn('action', function (Employee $employee) {
                $show = route('employees.show', $employee->id);
                $edit = route('employees.edit', $employee->id);
                $delete = route('employees.destroy', $employee->id);

                return '<div class="btn-group btn-group-sm">'
                    . '<a href="' . $show . '" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>'
                    . '<a href="' . $edit . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>'
                    . '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete(\'' . $delete . '\', \'Delete Employee\', \'Delete ' . e($employee->full_name) . '? This action cannot be undone.\')"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['photo', 'full_name', 'department', 'employee_id', 'status', 'action'])
            ->setRowId('id');
    }

    /**
     * The source query — request filters are applied here.
     */
    public function query(Employee $model): QueryBuilder
    {
        $query = $model->newQuery()->with('department');

        if ($departmentId = request('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($position = request('position')) {
            $query->where('position', 'like', "%{$position}%");
        }

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Table builder configuration.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('employees-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(7, 'desc') // hire date
            ->parameters([
                'dom' => '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }

    /**
     * Column definitions.
     */
    protected function getColumns(): array
    {
        return [
            Column::computed('photo')->title('Photo')->orderable(false)->searchable(false)->width(60),
            Column::make('employee_id')->title('Employee ID'),
            Column::computed('full_name')->title('Name')
                ->name('first_name'),
            Column::make('email')->title('Email'),
            Column::computed('department')->title('Department')->name('department.name')->orderable(false),
            Column::make('position')->title('Position'),
            Column::make('status')->title('Status'),
            Column::make('hire_date')->title('Hire Date'),
            Column::computed('action')->title('Actions')->orderable(false)->searchable(false)->width(110),
        ];
    }

    protected function filename(): string
    {
        return 'Employees_' . date('YmdHis');
    }

    /**
     * Render a status badge.
     */
    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'active' => 'bg-success',
            'inactive' => 'bg-warning',
            'terminated' => 'bg-danger',
            default => 'bg-secondary',
        };

        return '<span class="badge ' . $class . '">' . e(ucfirst($status)) . '</span>';
    }
}
