<?php

namespace App\DataTables;

use App\Models\Leave;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LeavesDataTable extends DataTable
{
    /**
     * Build the DataTable response for the logged-in employee's leave history.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('type', function (Leave $leave) {
                $color = $leave->getLeaveTypeColor() ?? '#6c757d';
                return '<span class="badge" style="background-color:' . e($color) . ';">' . e(ucfirst($leave->type)) . '</span>';
            })
            ->editColumn('start_date', fn (Leave $leave) => $leave->start_date?->format('M d, Y'))
            ->editColumn('end_date', fn (Leave $leave) => $leave->end_date?->format('M d, Y'))
            ->editColumn('reason', fn (Leave $leave) => '<span class="text-truncate d-inline-block" style="max-width:200px;" title="' . e($leave->reason) . '">' . e(Str::limit($leave->reason, 30)) . '</span>')
            ->editColumn('status', fn (Leave $leave) => $this->statusBadge($leave->status))
            ->editColumn('created_at', fn (Leave $leave) => $leave->created_at?->format('M d, Y'))
            ->addColumn('action', function (Leave $leave) {
                $show = route('leaves.show', $leave->id);
                $buttons = '<div class="btn-group btn-group-sm">'
                    . '<a href="' . $show . '" class="btn btn-sm btn-outline-primary" title="View"><i class="fas fa-eye"></i></a>';

                if ($leave->status === Leave::STATUS_PENDING) {
                    $buttons .= '<a href="' . route('leaves.edit', $leave->id) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>'
                        . '<button type="button" class="btn btn-sm btn-outline-danger" title="Cancel" onclick="cancelLeave(' . $leave->id . ', \'' . route('leaves.destroy', $leave->id) . '\')"><i class="fas fa-times"></i></button>';
                }

                return $buttons . '</div>';
            })
            ->rawColumns(['type', 'reason', 'status', 'action'])
            ->setRowId('id');
    }

    /**
     * Source query — scoped to the authenticated user's employee record.
     */
    public function query(Leave $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->where('employee_id', Auth::user()->employee?->id ?? 0);

        if ($status = request('status')) {
            $query->where('status', $status);
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

        return $query->orderBy('created_at', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('leaves-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(6, 'desc') // applied on
            ->parameters([
                'dom' => '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }

    protected function getColumns(): array
    {
        return [
            Column::make('type')->title('Leave Type'),
            Column::make('start_date')->title('Start Date'),
            Column::make('end_date')->title('End Date'),
            Column::make('days')->title('Days'),
            Column::make('reason')->title('Reason')->orderable(false),
            Column::make('status')->title('Status'),
            Column::make('created_at')->title('Applied On'),
            Column::computed('action')->title('Actions')->orderable(false)->searchable(false)->width(110),
        ];
    }

    protected function filename(): string
    {
        return 'Leaves_' . date('YmdHis');
    }

    private function statusBadge(string $status): string
    {
        $class = match ($status) {
            'approved' => 'bg-success',
            'pending' => 'bg-warning',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary',
        };

        return '<span class="badge ' . $class . '">' . e(ucfirst($status)) . '</span>';
    }
}
