<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

/**
 * Forma de cada item de la respuesta de `POST /listar-mis-cartas`
 * (Api\ResponderCarta::listaCartas). ARQ-006 (Paso A, Fase 5): única
 * Resource introducida hasta ahora — es el único endpoint de la API con
 * forma "de modelo"; el resto son mensajes de estado, no representaciones
 * de un recurso, y no se benefician de una Resource (ver
 * docs/auditoria/evaluacion-api-resources.md).
 *
 * Mismos 9 campos y mismos valores que el array armado a mano que
 * reemplaza — verificado con tests/Feature/Api/ListarMisCartasTest.php
 * antes y después de este cambio, sin diferencia.
 */
class CartaListaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'created_at' => $this->created_at->format('d/m/Y H:i:s'),
            'id' => $this->id,
            'asunto' => $this->asunto,
            'tipo_carta_nombre' => $this->tipo,
            'estado' => $this->estado,
            'edad' => Carbon::parse($this->ninio->fecha_nacimiento)->age ?? 0,
            'archivo' => $this->archivo_pdf ? route('verarchivopdfporninio', Crypt::encryptString($this->id)) : 'no',
            'carta' => $this->estado == 'Respondida' ? route('descargarCartaPdf', Crypt::encryptString($this->id)) : 'no',
            'carta_nombre' => $this->estado == 'Respondida' ? 'Carta-de-'.$this->tipo.'-'.$this->id.'.pdf' : '',
            'eliminado' => $this->eliminado,
        ];
    }
}
