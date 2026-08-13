<?php

namespace App\Http\Requests\Cartas;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Reglas de validación compartidas entre el canal Web (ResponderCartaController)
 * y el canal API/móvil (Api\ResponderCarta) para la respuesta de Presentación
 * (niños de 5 años o menos). La autorización real ocurre después, por canal
 * (ARQ-001) — no aquí.
 *
 * El campo 'mifamila' (sin "i") es un typo histórico compartido con el nombre
 * de campo real que envía CactuApp (ver PresentacionMenores.js) — no se
 * corrige aquí porque cambiarlo rompería el contrato con el móvil sin
 * coordinación previa.
 */
class ResponderCartaPresentacionMenorRequest extends FormRequest
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
            'escribo' => 'required',
            'mi' => 'required',
            'queel' => 'required',
            'cumple' => 'required',
            'noSabe' => 'required',
            'ademas' => 'required',
            'leGusta' => 'required',
            'dondeAprendo' => 'required',
            'gustaAprendes' => 'required',
            'mePaso' => 'required',
            'meGustaria' => 'required',
            'miNombre' => 'required',
            'ysoy' => 'required',
            'de' => 'required',
            'mifamila' => 'required',
            'nuestraPro' => 'required',
            'idioma' => 'required',
            'lugarFavorito' => 'required',
            'comidaTipica' => 'required',
            'ya' => 'required',
            'comer' => 'required',
            'masMeGusta' => 'required',
            'pregunta' => 'required',
            'despedida' => 'required',
        ];
    }
}
