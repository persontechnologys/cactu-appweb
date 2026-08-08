<?php

namespace App\DataTables;

use App\Models\Ninio;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class NinioDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function($n){
                return view('ninios.action',['n'=>$n])->render();
            })
            ->editColumn('comunidad_id',function($n){
                return $n->comunidad->nombre;
            })
            ->filterColumn('comunidad_id', function($query, $keyword) {
                $query->whereHas('comunidad', function($query) use ($keyword) {
                    $query->whereRaw("nombre like ?", ["%{$keyword}%"]);
                });
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Ninio $model): QueryBuilder
    {
        // defensa en profundidad (SEG-002): esta pantalla ya está restringida a
        // ADMINISTRADOR por middleware de rol; se replica igualmente el filtro
        // por comunidad usado en App\DataTables\Carta\NinioDataTable por si el
        // método se invocara alguna vez fuera de esa ruta.
        $user = Auth::user();
        if ($user && $user->hasRole('ADMINISTRADOR')) {
            return $model->newQuery();
        }

        return $model->whereIn('comunidad_id', $user?->comunidades->pluck('id') ?? []);
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('ninio-table')
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
            Column::make('numero_child')->title('N° child'),
            Column::make('nombres_completos'),
            Column::make('genero')->title('Género'),
            Column::make('fecha_nacimiento'),
            Column::make('edad'),
            Column::make('comunidad_id')->title('Comunidad'),
            Column::make('email'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Ninio_' . date('YmdHis');
    }
}
