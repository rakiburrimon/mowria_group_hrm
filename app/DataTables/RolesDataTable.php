<?php

namespace App\DataTables;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RolesDataTable extends DataTable
{
    /**
     * Build the DataTable response for roles.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('name', fn (Role $r) => '<span class="badge bg-dark">' . e($r->name) . '</span>')
            ->addColumn('permissions_count', fn (Role $r) => '<span class="badge bg-primary">' . $r->permissions_count . '</span>')
            ->addColumn('users_count', fn (Role $r) => '<span class="badge bg-info">' . $r->users_count . '</span>')
            ->editColumn('created_at', fn (Role $r) => $r->created_at?->format('M d, Y'))
            ->addColumn('action', function (Role $r) {
                $buttons = '<div class="btn-group btn-group-sm">'
                    . '<a href="' . route('roles.edit', $r->id) . '" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-edit"></i></a>';

                // super_admin role is protected
                if ($r->name !== 'super_admin') {
                    $buttons .= '<button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="confirmDelete(\'' . route('roles.destroy', $r->id) . '\', \'Delete Role\', \'Delete the ' . e($r->name) . ' role? This action cannot be undone.\')"><i class="fas fa-trash"></i></button>';
                }

                return $buttons . '</div>';
            })
            ->rawColumns(['name', 'permissions_count', 'users_count', 'action'])
            ->setRowId('id');
    }

    /**
     * Source query — roles with permission/user counts.
     */
    public function query(Role $model): QueryBuilder
    {
        return $model->newQuery()
            ->withCount(['permissions', 'users'])
            ->orderBy('name');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('roles-table')
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
            Column::make('name')->title('Role'),
            Column::computed('permissions_count')->title('Permissions'),
            Column::computed('users_count')->title('Users'),
            Column::make('created_at')->title('Created'),
            Column::computed('action')->title('Actions')->orderable(false)->searchable(false)->width(90),
        ];
    }

    protected function filename(): string
    {
        return 'Roles_' . date('YmdHis');
    }
}
