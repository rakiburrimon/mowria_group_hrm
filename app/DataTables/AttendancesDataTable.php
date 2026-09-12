<?php

namespace App\DataTables;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class AttendancesDataTable extends DataTable
{
    /**
     * Build the DataTable response for attendance records.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('employee', function (Attendance $attendance) {
                $employee = $attendance->employee;
                $avatar = $employee->profile_image
                    ? '<img src="' . asset('storage/' . $employee->profile_image) . '" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" alt="">'
                    : '<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><i class="fas fa-user fa-sm"></i></div>';

                return '<div class="d-flex align-items-center"><div class="me-2">' . $avatar . '</div>'
                    . '<div><div class="fw-bold">' . e($employee->full_name) . '</div>'
                    . '<small class="text-muted">' . e($employee->employee_id) . '</small></div></div>';
            })
            ->addColumn('department', fn (Attendance $a) => '<span class="badge bg-info">' . e($a->employee->department?->name ?? 'N/A') . '</span>')
            ->editColumn('date', fn (Attendance $a) => $a->date?->format('M d, Y'))
            ->editColumn('check_in', fn (Attendance $a) => $a->check_in
                ? '<span class="text-success fw-bold">' . e($a->check_in->format('H:i')) . '</span>'
                : '<span class="text-muted">-</span>')
            ->editColumn('check_out', fn (Attendance $a) => $a->check_out
                ? '<span class="text-danger fw-bold">' . e($a->check_out->format('H:i')) . '</span>'
                : '<span class="text-muted">-</span>')
            ->editColumn('work_hours', fn (Attendance $a) => $a->work_hours
                ? '<span class="text-primary fw-bold">' . e($a->formatted_work_hours) . '</span>'
                : '<span class="text-muted">-</span>')
            ->editColumn('late_minutes', fn (Attendance $a) => $a->late_minutes > 0
                ? '<span class="text-warning fw-bold">' . e($a->late_minutes) . ' min</span>'
                : '<span class="text-success fw-bold">On Time</span>')
            ->editColumn('early_leave_minutes', fn (Attendance $a) => $a->early_leave_minutes > 0
                ? '<span class="text-warning fw-bold">' . e($a->early_leave_minutes) . ' min</span>'
                : '<span class="text-success fw-bold">Full Day</span>')
            ->editColumn('status', fn (Attendance $a) => '<span class="badge bg-' . e($a->status_color) . '">' . e($a->status_label) . '</span>')
            ->addColumn('action', function (Attendance $a) {
                return '<div class="btn-group btn-group-sm">'
                    . '<a href="' . route('attendance.show', $a->id) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>'
                    . '<a href="' . route('attendance.edit', $a->id) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>'
                    . '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteAttendance(' . $a->id . ', \'' . route('attendance.destroy', $a->id) . '\')"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['employee', 'department', 'check_in', 'check_out', 'work_hours', 'late_minutes', 'early_leave_minutes', 'status', 'action'])
            ->setRowId('id');
    }

    /**
     * Source query — request filters applied.
     */
    public function query(Attendance $model): QueryBuilder
    {
        $query = $model->newQuery()->with('employee.department');

        if ($employeeId = request('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($departmentId = request('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($from = request('date_from')) {
            $query->where('date', '>=', $from);
        }

        if ($to = request('date_to')) {
            $query->where('date', '<=', $to);
        }

        return $query->orderBy('date', 'desc')->orderBy('check_in', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('attendances-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(2, 'desc') // date
            ->parameters([
                'dom' => '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }

    protected function getColumns(): array
    {
        return [
            Column::computed('employee')->title('Employee')->name('employee.first_name'),
            Column::computed('department')->title('Department')->name('employee.department.name')->orderable(false),
            Column::make('date')->title('Date'),
            Column::make('check_in')->title('Check In'),
            Column::make('check_out')->title('Check Out'),
            Column::make('work_hours')->title('Work Hours'),
            Column::make('late_minutes')->title('Late'),
            Column::make('early_leave_minutes')->title('Early Leave'),
            Column::make('status')->title('Status'),
            Column::computed('action')->title('Actions')->orderable(false)->searchable(false)->width(110),
        ];
    }

    protected function filename(): string
    {
        return 'Attendances_' . date('YmdHis');
    }
}
