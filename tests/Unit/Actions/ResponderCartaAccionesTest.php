<?php

namespace Tests\Unit\Actions;

use App\Actions\GuardarRespuestaCartaAction;
use App\Actions\ResponderCartaAgradecimientoAction;
use App\Actions\ResponderCartaContestacionAction;
use App\Actions\ResponderCartaIniciadaAction;
use App\Actions\ResponderCartaPresentacionMayorAction;
use App\Actions\ResponderCartaPresentacionMenorAction;
use App\Models\Carta;
use App\Models\Ninio;
use App\Notifications\EnviarCartaRespondidaGestor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * ARQ-001: fija el HTML de `detalle` que producían ResponderCartaController
 * (web) y Api\ResponderCarta (móvil) antes de extraer las Actions. El
 * `$esperado` de cada test se construye con la MISMA expresión de
 * concatenación que tenía el código original (copiada tal cual, solo con
 * variables locales en vez de $request->campo), para no depender de un
 * cálculo manual propenso a error al comparar contra la salida real.
 */
class ResponderCartaAccionesTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    private function crearCartaPendiente(): Carta
    {
        $gestor = $this->crearAdministrador();
        $comunidad = $this->crearComunidad($gestor);
        $ninio = Ninio::create([
            'numero_child' => 'EC-'.uniqid(),
            'nombres_completos' => 'Niño de prueba',
            'genero' => 'Male',
            'fecha_nacimiento' => '2015-01-01',
            'edad' => 10,
            'comunidad_id' => $comunidad->id,
        ]);

        Auth::login($gestor);
        $carta = Carta::create([
            'tipo' => 'Contestación',
            'asunto' => 'Asunto de prueba',
            'estado' => 'Enviado',
            'ninio_id' => $ninio->id,
            'comunidad_id' => $comunidad->id,
        ]);
        Auth::logout();

        return $carta;
    }

    public function test_contestacion_arma_el_detalle_esperado_y_marca_la_carta_respondida(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        $nombre_patrocinador = 'Juan';
        $agradezco_por = 'la comida';
        $te_cuento_que = 'estoy bien';
        $pregunta_al_patrocinador = 'cómo estás';
        $despedida = 'un abrazo';

        $esperado = '<p><b>Hola</b> '.$nombre_patrocinador.
                    ', agradesco por la '.$agradezco_por.'</p>'.
                    '<p>Te cuento que '.$te_cuento_que.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$pregunta_al_patrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$despedida.'</p>';

        $action = new ResponderCartaContestacionAction(new GuardarRespuestaCartaAction());
        $action->ejecutar(
            $carta,
            $nombre_patrocinador,
            $agradezco_por,
            $te_cuento_que,
            $pregunta_al_patrocinador,
            $despedida,
            UploadedFile::fake()->image('foto.jpg'),
            null
        );

        $carta->refresh();
        $this->assertSame($esperado, $carta->detalle);
        $this->assertSame('Respondida', $carta->estado);
        $this->assertNotNull($carta->fecha_respondida);
        Storage::disk('local')->assertExists($carta->archivo_imagen_ninio);
        Notification::assertSentTo($carta->gestor, EnviarCartaRespondidaGestor::class);
    }

    /**
     * Comportamiento heredado de Api\ResponderCarta::guardarCarta: si el
     * archivo de imagen no llegó como upload válido (hasFile()/isValid()
     * en falso pese a que 'imagen' es 'required' en la validación), la
     * carta igual se marca "Respondida" sin archivo — no se lanza error.
     * GuardarRespuestaCartaAction preserva ese comportamiento aceptando
     * $imagen nulo.
     */
    public function test_guardar_respuesta_sin_imagen_valida_igual_marca_la_carta_respondida(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        $action = new GuardarRespuestaCartaAction();
        $action->guardar($carta, '<p>detalle</p>', null, null);

        $carta->refresh();
        $this->assertSame('Respondida', $carta->estado);
        $this->assertNull($carta->archivo_imagen_ninio);
    }

    public function test_agradecimiento_arma_el_detalle_esperado(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        $nombre_patrocinador = 'Juan';
        $agradezco_por = '50 dólares';
        $regalo_usar_para = 'útiles escolares';
        $te_cuento_que = 'estoy bien';
        $pregunta_al_patrocinador = 'cómo estás';
        $despedida = 'un abrazo';

        $esperado = '<p><b>Hola</b> '.$nombre_patrocinador.
                    ', Agradezco por el valor enviado de '.$agradezco_por.'</p>'.
                    '<p>Tu regalo lo voy a usar para '.$regalo_usar_para.'</p>'.
                    '<p>Te cuento que '.$te_cuento_que.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$pregunta_al_patrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$despedida.'</p>';

        $action = new ResponderCartaAgradecimientoAction(new GuardarRespuestaCartaAction());
        $action->ejecutar(
            $carta,
            $nombre_patrocinador,
            $agradezco_por,
            $regalo_usar_para,
            $te_cuento_que,
            $pregunta_al_patrocinador,
            $despedida,
            UploadedFile::fake()->image('foto.jpg'),
            null
        );

        $this->assertSame($esperado, $carta->refresh()->detalle);
    }

    public function test_iniciada_arma_el_detalle_esperado(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        $nombre_patrocinador = 'Juan';
        $te_cuento_que = 'estoy bien';
        $en_cactu_aprendi = 'a leer';
        $pregunta_al_patrocinador = 'cómo estás';
        $despedida = 'un abrazo';

        $esperado = '<p><b>Hola</b> '.$nombre_patrocinador.'</p>'.
                    '<p>Te cuento que '.$te_cuento_que.'</p>'.
                    '<p>Gracias a ChildFund, en CACTU aprendí '.$en_cactu_aprendi.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$pregunta_al_patrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$despedida.'</p>';

        $action = new ResponderCartaIniciadaAction(new GuardarRespuestaCartaAction());
        $action->ejecutar(
            $carta,
            $nombre_patrocinador,
            $te_cuento_que,
            $en_cactu_aprendi,
            $pregunta_al_patrocinador,
            $despedida,
            UploadedFile::fake()->image('foto.jpg'),
            null
        );

        $this->assertSame($esperado, $carta->refresh()->detalle);
    }

    public function test_presentacion_mayor_arma_el_detalle_esperado(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        $hola = 'Pedro'; $soy = 'Pedro'; $meDicen = 'Pedrito'; $edad = '10 años';
        $miMejorAmigo = 'Luis'; $esMejorAmigo = 'me ayuda'; $loquehago = 'jugar fútbol';
        $miSueno = 'ser doctor'; $dondeAprendo = 'la escuela'; $gustaAprendes = 'matemáticas';
        $mePaso = 'gané un premio'; $meGustaria = 'inglés'; $miFamilia = 'papá y mamá';
        $nuestraPro = 'Manabí'; $idioma = 'español'; $lugarFavorito = 'el río';
        $comidaTipica = 'ceviche'; $comer = 'arroz'; $masMeGusta = 'la música';
        $pregunta = 'cuál es tu color favorito'; $despedida = 'hasta pronto';

        $esperado = '<p><b>Hola '.$hola.
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

        $action = new ResponderCartaPresentacionMayorAction(new GuardarRespuestaCartaAction());
        $action->ejecutar(
            $carta,
            $hola, $soy, $meDicen, $edad, $miMejorAmigo, $esMejorAmigo, $loquehago,
            $miSueno, $dondeAprendo, $gustaAprendes, $mePaso, $meGustaria, $miFamilia,
            $nuestraPro, $idioma, $lugarFavorito, $comidaTipica, $comer, $masMeGusta,
            $pregunta, $despedida,
            UploadedFile::fake()->image('foto.jpg'),
            UploadedFile::fake()->image('familia.jpg')
        );

        $this->assertSame($esperado, $carta->refresh()->detalle);
        Storage::disk('local')->assertExists($carta->archivo_familia_ninio);
    }

    public function test_presentacion_menor_arma_el_detalle_esperado(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        $hola = 'la familia García'; $escribo = 'mi hermanita'; $mi = 'hermanita';
        $queel = 'la '; $cumple = '3 años'; $noSabe = 'ya dice palabras';
        $ademas = 'ella'; $leGusta = ' bailar'; $dondeAprendo = 'la casa';
        $gustaAprendes = 'colores'; $mePaso = 'caminó sola'; $meGustaria = ' hablar mejor';
        $miNombre = 'Ana'; $ysoy = ' su hermana'; $de = ' Loja';
        $mifamila = 'papá, mamá y yo'; $nuestraPro = 'Loja'; $idioma = ' español';
        $lugarFavorito = 'el parque'; $comidaTipica = 'cecina'; $ya = 'ella';
        $comer = ' fruta'; $masMeGusta = 'la navidad'; $pregunta = 'tienes hijos';
        $despedida = 'un beso';

        $esperado =
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

        $action = new ResponderCartaPresentacionMenorAction(new GuardarRespuestaCartaAction());
        $action->ejecutar(
            $carta,
            $hola, $escribo, $mi, $queel, $cumple, $noSabe, $ademas, $leGusta,
            $dondeAprendo, $gustaAprendes, $mePaso, $meGustaria, $miNombre, $ysoy,
            $de, $mifamila, $nuestraPro, $idioma, $lugarFavorito, $comidaTipica,
            $ya, $comer, $masMeGusta, $pregunta, $despedida,
            UploadedFile::fake()->image('foto.jpg'),
            UploadedFile::fake()->image('familia.jpg')
        );

        $this->assertSame($esperado, $carta->refresh()->detalle);
        Storage::disk('local')->assertExists($carta->archivo_familia_ninio);
    }

    public function test_la_foto_se_guarda_con_la_extension_de_su_contenido_y_no_la_del_nombre(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        // CactuApp envía JPEG con el nombre "personal.png".
        $jpegConNombrePng = UploadedFile::fake()->image('personal.png')->mimeType('image/jpeg');
        $contenidoJpeg = UploadedFile::fake()->image('foto.jpg');
        file_put_contents($jpegConNombrePng->getPathname(), file_get_contents($contenidoJpeg->getPathname()));

        (new GuardarRespuestaCartaAction())->guardar($carta, 'detalle', $jpegConNombrePng, null);

        $carta->refresh();
        $this->assertMatchesRegularExpression('/\.(jpg|jpeg)$/', $carta->archivo_imagen_ninio);
        Storage::disk('local')->assertExists($carta->archivo_imagen_ninio);
    }
}
