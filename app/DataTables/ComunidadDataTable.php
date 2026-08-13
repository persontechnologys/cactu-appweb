<?php

namespace App\DataTables;

use App\Models\Comunidad;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ComunidadDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function($c){
                return view('comunidad.action',['comunidad'=>$c])->render();
            })
            ->filterColumn('user_id', function($query, $keyword) {
                $query->whereHas('user', function($query) use ($keyword) {
                    $query->whereRaw("concat(name,'',email) like ?", ["%{$keyword}%"]);
                });
            })
            ->editColumn('user_id',function($c){
                if($c->user){
                    return $c->user->name.' '.$c->user->email;
                }else{
                    return '';
                }
            })
            ->setRowId('id');

    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Comunidad $model): QueryBuilder
    {
        return $this->applyFilters($model->newQuery());
    }

    /**
     * Aplicar filtros de búsqueda
     */
    private function applyFilters($query)
    {
        $request = request();

        if ($request->filled('gestor_filter') && $request->gestor_filter !== 'all') {
            $query->where('user_id', $request->gestor_filter);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('comunidad-table')
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
            Column::make('nombre'),
            Column::make('user_id')->title('Usuario gestor')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Comunidad_' . date('YmdHis');
    }
}
