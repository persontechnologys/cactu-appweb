<?php

namespace App\Actions;

use App\Models\Carta;
use Illuminate\Http\UploadedFile;

class ResponderCartaPresentacionMayorAction
{
    public function __construct(private GuardarRespuestaCartaAction $guardarRespuesta)
    {
    }

    /**
     * Arma el detalle de una respuesta tipo "Presentación" (niño mayor) y la
     * guarda. Plantilla extraída sin cambios de
     * ResponderCartaController::guardarPresentacionMayor (web) y
     * Api\ResponderCarta::responderCartaPresentacionMayor (móvil) — ARQ-001.
     */
    public function ejecutar(
        Carta $carta,
        string $hola,
        string $soy,
        string $meDicen,
        string $edad,
        string $miMejorAmigo,
        string $esMejorAmigo,
        string $loquehago,
        string $miSueno,
        string $dondeAprendo,
        string $gustaAprendes,
        string $mePaso,
        string $meGustaria,
        string $miFamilia,
        string $nuestraPro,
        string $idioma,
        string $lugarFavorito,
        string $comidaTipica,
        string $comer,
        string $masMeGusta,
        string $pregunta,
        string $despedida,
        ?UploadedFile $imagen,
        ?UploadedFile $fotoFamilia
    ): Carta {
        $detalle = '<p><b>Hola '.$hola.
            '</b></p><p> Soy '.$soy.
            ' y mis amigos me dicen '.$meDicen.
            '</p><p> tengo '.$edad.
            '</p><p> Mi mejor amigo se llama '.$miMejorAmigo.
            ' es mi mejor amigo porque, '.$esMejorAmigo.
            '</p><p> Lo que maś me gusta hacer es, '.$loquehago.
            '</p><p> Cuando sea grande mi sueño es '.$miSueno.
            '</p><p> El lugar donde aprendo es, '.$dondeAprendo.
            ' lo que me gusta aprender es, '.$gustaAprendes.
            '</p><p> Lo más importante que me pasó últimamente es '.$mePaso.
            '</p><p> Lo que me gustaría aprender en el programa de ChildFund es '.$meGustaria.
            '</p><p> Esta es mi famila '.$miFamilia.
            '</p><p><b>También quiero contarte del lugar donde vivo.'.
            '</b></p><p> Nuestra provincia se llama '.$nuestraPro.
            ' y el idioma que hablamos es '.$idioma.
            '</p><p>Donde nosotros vivimos hay sitios muy hermosos,'.
            ' mi lugar favorito es '.$lugarFavorito.
            '</p><p>También tenemos comida típica, por ejemplo:'.
            '</p><p> La comida típica de esta región es '.$comidaTipica.
            ' y a mi me gusta comer '.$comer.
            '</p><p> De nuestras tradiciones, lo que más me gusta es '.$masMeGusta.
            '</p><p><b> Me gustaría hacerte una pregunta</b></p><p>¿ '.$pregunta.
            '?</p><p> Cuéntale por qué quisieras que te conteste tu patrocinador, envíale un mensaje de despedida </p><p>'.$despedida.
            '</p>';

        return $this->guardarRespuesta->guardar($carta, $detalle, $imagen, $fotoFamilia);
    }
}
