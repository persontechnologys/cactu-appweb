<?php

namespace App\Actions;

use App\Models\Carta;
use Illuminate\Http\UploadedFile;

class ResponderCartaPresentacionMenorAction
{
    public function __construct(private GuardarRespuestaCartaAction $guardarRespuesta)
    {
    }

    /**
     * Arma el detalle de una respuesta tipo "Presentación" (niño menor) y la
     * guarda. Plantilla extraída sin cambios de
     * ResponderCartaController::guardarPresentacionMenor (web) y
     * Api\ResponderCarta::responderCartaPresentacionMenor (móvil) — ARQ-001.
     */
    public function ejecutar(
        Carta $carta,
        string $hola,
        string $escribo,
        string $mi,
        string $queel,
        string $cumple,
        string $noSabe,
        string $ademas,
        string $leGusta,
        string $dondeAprendo,
        string $gustaAprendes,
        string $mePaso,
        string $meGustaria,
        string $miNombre,
        string $ysoy,
        string $de,
        string $mifamila,
        string $nuestraPro,
        string $idioma,
        string $lugarFavorito,
        string $comidaTipica,
        string $ya,
        string $comer,
        string $masMeGusta,
        string $pregunta,
        string $despedida,
        ?UploadedFile $imagen,
        ?UploadedFile $fotoFamilia
    ): Carta {
        $detalle =
            '<p><b>Hola '.$hola.'</b></p>'.
            '<p>Escribo a nombre de '.$escribo.''.
            ' mi '.$mi.' '.
            ', que el'.$queel.'</p>'.
            '<p>Cumple '.$cumple.' '.
            'de edad y aún no sabe escribir pero '.$noSabe.'</p>'.
            '<p>Además a '.$ademas.' '.
            'le gusta'.$leGusta.'</p>'.
            '<p>El lugar donde aprende es '.$dondeAprendo.'</p>'.
            '<p>En este mes aprendimos '.$gustaAprendes.' '.
            'y lo más importante que nos pasó últimamente es '.$mePaso.'</p>'.
            '<p>Lo que esperamos aprender con el programa de ChildFund es'.$meGustaria.'</p>'.
            '<p>Mi nombre es '.$miNombre.' '.
            'y soy'.$ysoy.' '.
            'de'.$de.'</p>'.
            '<p>Los otros miembros de nuestra familia son '.$mifamila.'</p>'.
            '<p>Nosotros vivimos en la provincia de '.$nuestraPro.' '.
            'y el idioma que hablamos es'.$idioma.'</p>'.
            '<p>Nuestra provincia tiene sitios muy hermosos, a nosotros nos gusta ir a '.$lugarFavorito.'</p>'.
            '<p>También tenemos comida típica, por ejemplo '.$comidaTipica.' '.
            'y a '.$ya.' '.
            'le gusta'.$comer.'</p>'.
            '<p>De nuestras tradiciones, la que compartimos juntos es '.$masMeGusta.'</p>'.
            '<p>Nos gustaría saber más sobre ti y tu familia y hacerles una pregunta</p><p><b>¿ '.$pregunta.'?</b></p>'.
            '<p>Cuéntale por qué quisieras que te conteste tu patrocinador, envíale un mensaje de despedida:</p><p> '.$despedida.'</p>';

        return $this->guardarRespuesta->guardar($carta, $detalle, $imagen, $fotoFamilia);
    }
}
