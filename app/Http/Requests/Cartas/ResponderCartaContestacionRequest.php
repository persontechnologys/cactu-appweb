<?php

namespace App\Http\Requests\Cartas;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reglas de validación compartidas entre el canal Web (ResponderCartaController)
 * y el canal API/móvil (Api\ResponderCarta) para la respuesta de Contestación.
 * La autorización real ocurre después, por canal (ARQ-001) — no aquí.
 */
class ResponderCartaContestacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => 'required',
            'imagen' => 'required',
            'detalle' => 'required',
        ];
    }
}
