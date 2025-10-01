<?php

namespace App\DataTables;

use App\Models\Carta;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Crypt;

class CartaDataTable extends DataTable
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
                return view('cartas.action',['c'=>$c])->render();
            })
            
            ->setRowClass(function ($c) {
                return $c->estado === 'Respondida' ? 'table-success' : 'table-warning';
            })
            ->editColumn('created_at', function ($c) {
                // Formato simple; tu app ya está en America/Guayaquil
                $fmt = 'd/m/Y H:i';

                $fecha = optional($c->created_at)->format($fmt);

                return $fecha ? '<div class="small text-muted mt-1"><time>'.e($fecha).'</time></div>' : '';
            })
            ->editColumn('tipo', function ($c) {
                return '<span class="badge bg-'.$c->color.'"><i class="'.$c->icono.' me-1"></i>'.e($c->tipo).'</span>';
            })
            ->editColumn('estado', function ($c) {
                return '<span class="badge bg-'.$c->estado_color.'">'.e($c->estado ?? 'Enviado').'</span>';
            })
            ->editColumn('fecha_respondida', function ($c) {
                // Formato simple; tu app ya está en America/Guayaquil
                $fmt = 'd/m/Y H:i';

                $fecha = optional($c->fecha_respondida)->format($fmt);

                return $fecha ? '<div class="small text-muted mt-1"><time>'.e($fecha).'</time></div>' : '';
            })


           
            ->rawColumns(['asunto','action','estado','created_at','tipo','fecha_respondida','archivos'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Carta $model): QueryBuilder
    {
        $user = Auth::user();
        $query = $user->hasRole('ADMINISTRADOR') 
            ? $model->newQuery()->with(['ninio', 'gestor', 'comunidad'])
            : $model->where('user_id', $user->id)->with(['ninio', 'gestor', 'comunidad']);

        // Aplicar filtros
        $query = $this->applyFilters($query);

        return $query->latest();
    }

    /**
     * Aplicar filtros de búsqueda
     */
    private function applyFilters($query)
    {
        $request = request();

        // Filtro por tipo de carta
        if ($request->filled('tipo_filter') && $request->tipo_filter !== 'all') {
            $query->where('tipo', $request->tipo_filter);
        }

        // Filtro por estado
        if ($request->filled('estado_filter') && $request->estado_filter !== 'all') {
            $query->where('estado', $request->estado_filter);
        }

        // Filtro por rango de fechas de creación
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('created_at', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('created_at', '<=', $request->fecha_fin);
        }

        // Filtro por rango de fechas de respuesta
        if ($request->filled('fecha_respuesta_inicio')) {
            $query->whereDate('fecha_respondida', '>=', $request->fecha_respuesta_inicio);
        }

        if ($request->filled('fecha_respuesta_fin')) {
            $query->whereDate('fecha_respondida', '<=', $request->fecha_respuesta_fin);
        }

        // Filtro por niño (búsqueda por número child o nombre)
        if ($request->filled('ninio_filter')) {
            $query->whereHas('ninio', function ($q) use ($request) {
                $q->where('numero_child', 'LIKE', '%' . $request->ninio_filter . '%')
                  ->orWhere('nombres_completos', 'LIKE', '%' . $request->ninio_filter . '%');
            });
        }

        // Filtro por comunidad
        if ($request->filled('comunidad_filter') && $request->comunidad_filter !== 'all') {
            $query->where('comunidad_id', $request->comunidad_filter);
        }

        // Filtro por gestor
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
                    ->setTableId('carta-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->orderBy(7, 'desc') // Ordenar por fecha de creación descendente
                    ->selectStyleSingle()
                    ->parameters($this->getBuilderParameters());
    }

    /**
     * Get builder parameters.
     */
    protected function getBuilderParameters(): array
    {
        return array_merge(parent::getBuilderParameters(), [
            'drawCallback' => 'function() { 
                var info = this.api().page.info();
                $("#registros-info").text("Mostrando " + (info.start + 1) + " a " + info.end + " de " + info.recordsDisplay + " registros");
            }',
            'stateSave' => false, // Deshabilitado para evitar conflictos con filtros
        ]);
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
            Column::computed('ninio.numero_child')->title('Número Child')->searchable(true),
            Column::make('ninio.nombres_completos')->title('Niño'),
            Column::make('asunto'),
            Column::make('tipo')->title('Tipo de carta')->addClass('text-center'),
            Column::make('estado')->addClass('text-center'),
   
            Column::make('gestor.email')->title('Gestor'),
            Column::make('created_at')->title('Fecha creación')->searchable(false),
            Column::make('fecha_respondida')->title('Fecha respondida')->searchable(false),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Carta_' . date('YmdHis');
    }
}
