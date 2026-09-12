<?php

namespace App\DataTables;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DepartmentsDataTable extends DataTable
{
    /**
     * Build the DataTable response for departments.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('employees_count', fn (Department $d) => '<span class="badge bg-primary">' . $d->employees_count . '</span>')
            ->editColumn('status', fn (Department $d) => $d->status === 'active'
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->editColumn('created_at', fn (Department $d) => $d->created_at?->format('M d, Y'))
            ->addColumn('action', function (Department $d) {
                return '<div class="btn-group btn-group-sm">'
                    . '<a href="' . route('departments.edit', $d->id) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>'
                    . '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete(\'' . route('departments.destroy', $d->id) . '\', \'Delete Department\', \'Delete ' . e($d->name) . '? This action cannot be undone.\')"><i class="fas fa-trash"></i></button>'
                    . '</div>';
            })
            ->rawColumns(['employees_count', 'status', 'action'])
            ->setRowId('id');
    }

    /**
     * Source query — departments with employee counts.
     */
    public function query(Department $model): QueryBuilder
    {
        $query = $model->newQuery()->withCount('employees');

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        return $query->orderBy('name');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('departments-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0)
            ->parameters([
                'dom' => '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
                'responsive' => true,
                'autoWidth' => false,
            ]);
    }

    protected function getColumns(): array
    {
        return [
            Column::make('name')->title('Name'),
            Column::make('description')->title('Description'),
            Column::make('employees_count')->title('Employees')->searchable(false),
            Column::make('status')->title('Status'),
            Column::make('created_at')->title('Created'),
            Column::computed('action')->title('Actions')->orderable(false)->searchable(false)->width(90),
        ];
    }

    protected function filename(): string
    {
        return 'Departments_' . date('YmdHis');
    }
}
