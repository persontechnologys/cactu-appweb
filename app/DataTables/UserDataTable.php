<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class UserDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function($user){
                return view('usuarios.action',['user'=>$user]);
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        return $this->applyFilters($model->newQuery());
    }

    /**
     * Aplicar filtros de búsqueda
     */
    private function applyFilters($query)
    {
        $request = request();

        if ($request->filled('rol_filter') && $request->rol_filter !== 'all') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('id', $request->rol_filter);
            });
        }

        if ($request->filled('estado_filter') && $request->estado_filter !== 'all') {
            $query->where('estado', $request->estado_filter);
        }

        if ($request->filled('provincia_filter') && $request->provincia_filter !== 'all') {
            $query->where('provincia', $request->provincia_filter);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('users-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->parameters($this->getBuilderParameters());
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->title('Acción')
                  ->addClass('text-center'),
            Column::make('name')->title('Usuario'),
            Column::make('email'),
            Column::make('provincia'),
            Column::make('estado'),
            
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'User_' . date('YmdHis');
    }
}
