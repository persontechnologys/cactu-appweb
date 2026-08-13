<?php

namespace App\Http\Requests\Cartas;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reglas de validación compartidas entre el canal Web (ResponderCartaController)
 * y el canal API/móvil (Api\ResponderCarta) para la respuesta de Presentación
 * (niños mayores de 5 años). La autorización real ocurre después, por canal
 * (ARQ-001) — no aquí.
 */
class ResponderCartaPresentacionMayorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagen' => 'required',
            'foto_familia' => 'required',
            'id' => 'required',
            'hola' => 'required',
            'soy' => 'required',
            'meDicen' => 'required',
            'edad' => 'required',
            'miMejorAmigo' => 'required',
            'esMejorAmigo' => 'required',
            'loquehago' => 'required',
            'miSueno' => 'required',
            'dondeAprendo' => 'required',
            'gustaAprendes' => 'required',
            'mePaso' => 'required',
            'meGustaria' => 'required',
            'miFamilia' => 'required',
            'nuestraPro' => 'required',
            'idioma' => 'required',
            'lugarFavorito' => 'required',
            'comidaTipica' => 'required',
            'comer' => 'required',
            'masMeGusta' => 'required',
            'pregunta' => 'required',
            'despedida' => 'required',
        ];
    }
}
