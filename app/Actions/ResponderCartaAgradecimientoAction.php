<?php

namespace App\Actions;

use App\Models\Carta;
use Illuminate\Http\UploadedFile;

class ResponderCartaAgradecimientoAction
{
    public function __construct(private GuardarRespuestaCartaAction $guardarRespuesta)
    {
    }

    /**
     * Arma el detalle de una respuesta tipo "Agradecimiento" y la guarda.
     * Plantilla extraída sin cambios de ResponderCartaController::guardarAgradecimiento
     * (web) y Api\ResponderCarta::responderAgradecimineto (móvil) — ARQ-001.
     */
    public function ejecutar(
        Carta $carta,
        ?string $nombrePatrocinador,
        ?string $agradezcoPor,
        ?string $regaloUsarPara,
        ?string $teCuentoQue,
        ?string $preguntaAlPatrocinador,
        ?string $despedida,
        ?UploadedFile $imagen,
        ?UploadedFile $fotoFamilia
    ): Carta {
        $detalle = '<p><b>Hola</b> '.$nombrePatrocinador.
                    ', Agradezco por el valor enviado de '.$agradezcoPor.'</p>'.
                    '<p>Tu regalo lo voy a usar para '.$regaloUsarPara.'</p>'.
                    '<p>Te cuento que '.$teCuentoQue.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$preguntaAlPatrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$despedida.'</p>';

        return $this->guardarRespuesta->guardar($carta, $detalle, $imagen, $fotoFamilia);
    }
}
