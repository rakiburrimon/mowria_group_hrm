<?php

namespace App\DataTables;

use App\Models\Leave;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LeaveApprovalsDataTable extends DataTable
{
    /**
     * Pending leave requests for the approval panel.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('employee', function (Leave $leave) {
                $employee = $leave->employee;
                $avatar = $employee->profile_image
                    ? '<img src="' . asset('storage/' . $employee->profile_image) . '" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;" alt="">'
                    : '<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;"><i class="fas fa-user fa-sm"></i></div>';

                return '<div class="d-flex align-items-center"><div class="me-2">' . $avatar . '</div>'
                    . '<div><div class="fw-bold">' . e($employee->full_name) . '</div>'
                    . '<small class="text-muted">' . e($employee->employee_id) . '</small></div></div>';
            })
            ->addColumn('department', fn (Leave $leave) => '<span class="badge bg-info">' . e($leave->employee->department?->name ?? 'N/A') . '</span>')
            ->editColumn('type', function (Leave $leave) {
                $color = $leave->getLeaveTypeColor() ?? '#6c757d';
                return '<span class="badge" style="background-color:' . e($color) . ';">' . e(ucfirst($leave->type)) . '</span>';
            })
            ->editColumn('start_date', fn (Leave $leave) => $leave->start_date?->format('M d, Y'))
            ->editColumn('end_date', fn (Leave $leave) => $leave->end_date?->format('M d, Y'))
            ->editColumn('reason', fn (Leave $leave) => '<span class="text-truncate d-inline-block" style="max-width:150px;" title="' . e($leave->reason) . '">' . e(Str::limit($leave->reason, 25)) . '</span>')
            ->editColumn('created_at', fn (Leave $leave) => $leave->created_at?->format('M d, Y'))
            ->addColumn('action', function (Leave $leave) {
                return '<div class="btn-group btn-group-sm">'
                    . '<button type="button" class="btn btn-sm btn-success" title="Approve" onclick="showApprovalModal(' . $leave->id . ', \'approved\')"><i class="fas fa-check"></i></button>'
                    . '<button type="button" class="btn btn-sm btn-danger" title="Reject" onclick="showApprovalModal(' . $leave->id . ', \'rejected\')"><i class="fas fa-times"></i></button>'
                    . '<a href="' . route('leaves.show', $leave->id) . '" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>'
                    . '</div>';
            })
            ->rawColumns(['employee', 'department', 'type', 'reason', 'action'])
            ->setRowId('id');
    }

    /**
     * Source query — pending leaves only, with filters.
     */
    public function query(Leave $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['employee.department', 'approvals.approver'])
            ->where('status', Leave::STATUS_PENDING);

        if ($departmentId = request('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $departmentId));
        }

        if ($type = request('type')) {
            $query->where('type', $type);
        }

        if ($from = request('date_from')) {
            $query->where('start_date', '>=', $from);
        }

        if ($to = request('date_to')) {
            $query->where('end_date', '<=', $to);
        }

        return $query->orderBy('start_date', 'asc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('leave-approvals-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(3, 'asc') // start date
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
            Column::make('type')->title('Leave Type'),
            Column::make('start_date')->title('Start Date'),
            Column::make('end_date')->title('End Date'),
            Column::make('days')->title('Days'),
            Column::make('reason')->title('Reason')->orderable(false),
            Column::make('created_at')->title('Applied On'),
            Column::computed('action')->title('Actions')->orderable(false)->searchable(false)->width(110),
        ];
    }

    protected function filename(): string
    {
        return 'LeaveApprovals_' . date('YmdHis');
    }
}
