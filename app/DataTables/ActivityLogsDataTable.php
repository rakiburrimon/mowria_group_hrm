<?php

namespace App\DataTables;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ActivityLogsDataTable extends DataTable
{
    /**
     * Build the DataTable response for the audit trail.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('log_name', fn (Activity $a) => '<span class="badge bg-secondary">' . e($a->log_name) . '</span>')
            ->editColumn('description', fn (Activity $a) => e($a->description))
            ->addColumn('subject', function (Activity $a) {
                if (! $a->subject_type) {
                    return '<span class="text-muted">—</span>';
                }

                $name = class_basename($a->subject_type);
                $label = e($name . ' #' . $a->subject_id);

                // Add extra context when the subject still exists
                $subject = $a->subject;
                if ($subject) {
                    $display = $subject->full_name
                        ?? $subject->name
                        ?? $subject->title
                        ?? null;
                    if ($display) {
                        $label .= ' <small class="text-muted">(' . e($display) . ')</small>';
                    }
                }

                return $label;
            })
            ->addColumn('causer', function (Activity $a) {
                $causer = $a->causer;
                if (! $causer) {
                    return '<span class="text-muted">System</span>';
                }

                return '<div><div class="fw-semibold">' . e($causer->name ?? 'User #' . $causer->id) . '</div>'
                    . '<small class="text-muted">' . e($causer->email ?? '') . '</small></div>';
            })
            ->editColumn('event', function (Activity $a) {
                $class = match ($a->event) {
                    'created' => 'bg-success',
                    'updated' => 'bg-warning',
                    'deleted' => 'bg-danger',
                    default => 'bg-info',
                };

                return '<span class="badge ' . $class . '">' . e(ucfirst($a->event ?? 'action')) . '</span>';
            })
            ->editColumn('created_at', fn (Activity $a) => $a->created_at?->format('M d, Y H:i'))
            ->addColumn('action', function (Activity $a) {
                return '<button type="button" class="btn btn-sm btn-outline-primary" title="Details" '
                    . 'onclick="showActivityDetail(' . $a->id . ')">'
                    . '<i class="fas fa-eye"></i></button>';
            })
            ->rawColumns(['log_name', 'subject', 'causer', 'event', 'action'])
            ->setRowId('id');
    }

    /**
     * Source query — latest first, causer/subject eager loaded.
     */
    public function query(Activity $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['causer', 'subject']);

        if ($logName = request('log_name')) {
            $query->where('log_name', $logName);
        }

        if ($event = request('event')) {
            $query->where('event', $event);
        }

        if ($from = request('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = request('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('activity-logs-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->parameters([
                'dom' => '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }

    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('#')->width(60),
            Column::make('log_name')->title('Channel'),
            Column::make('description')->title('Action'),
            Column::computed('subject')->title('Subject')->name('subject_type'),
            Column::computed('causer')->title('Performed By')->name('causer_id')->orderable(false),
            Column::make('event')->title('Event'),
            Column::make('created_at')->title('Date'),
            Column::computed('action')->title('')->orderable(false)->searchable(false)->width(60),
        ];
    }

    protected function filename(): string
    {
        return 'ActivityLogs_' . date('YmdHis');
    }
}
