<?php

namespace App\Actions;

use App\Models\Carta;
use Illuminate\Http\UploadedFile;

class ResponderCartaIniciadaAction
{
    public function __construct(private GuardarRespuestaCartaAction $guardarRespuesta)
    {
    }

    /**
     * Arma el detalle de una respuesta tipo "Iniciada" y la guarda.
     * Plantilla extraída sin cambios de ResponderCartaController::guardarIniciada
     * (web) y Api\ResponderCarta::responderIniciada (móvil) — ARQ-001.
     */
    public function ejecutar(
        Carta $carta,
        ?string $nombrePatrocinador,
        ?string $teCuentoQue,
        ?string $enCactuAprendi,
        ?string $preguntaAlPatrocinador,
        ?string $despedida,
        ?UploadedFile $imagen,
        ?UploadedFile $fotoFamilia
    ): Carta {
        $detalle = '<p><b>Hola</b> '.$nombrePatrocinador.'</p>'.
                    '<p>Te cuento que '.$teCuentoQue.'</p>'.
                    '<p>Gracias a ChildFund, en CACTU aprendí '.$enCactuAprendi.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$preguntaAlPatrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$despedida.'</p>';

        return $this->guardarRespuesta->guardar($carta, $detalle, $imagen, $fotoFamilia);
    }
}
