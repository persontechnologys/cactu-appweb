<?php

namespace Tests\Feature\Cartas;

use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaUsuariosDePrueba;
use Tests\TestCase;

/**
 * ARQ-001: test de regresión end-to-end (ruta -> controller -> Action) del
 * canal Web de "responder carta", escrito tras extraer las Actions para
 * comprobar que el comportamiento observable no cambió.
 */
class ResponderCartaWebTest extends TestCase
{
    use RefreshDatabase, CreaUsuariosDePrueba;

    // PNG 1x1 transparente, igual al formato que produce el canvas del formulario web.
    private const IMAGEN_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

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

    /**
     * SEG-015 (parte 2): procesarRespuesta() ahora exige, además de los
     * campos cifrados del formulario, una sesión activa que coincida con el
     * niño (abierta acá simulando que ya pasó por ingresar()/ver() — el
     * link cifrado por sí solo ya no alcanza).
     */
    private function conSesionDe(Ninio $ninio): self
    {
        $this->withSession([
            'responder_carta_ninio_numero_child' => $ninio->numero_child,
            'responder_carta_ninio_expira' => now()->addMinutes(45),
        ]);

        return $this;
    }

    public function test_el_ninio_puede_responder_una_carta_de_contestacion_via_link_cifrado(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        $response = $this->conSesionDe($carta->ninio)->post(route('cartas-ninio.guardar-contestacion'), [
            'id' => Crypt::encryptString($carta->id),
            'ninio' => Crypt::encryptString($carta->ninio->numero_child),
            'imagen' => self::IMAGEN_BASE64,
            'nombre_patrocinador' => 'Juan',
            'agradezco_por' => 'la comida',
            'te_cuento_que' => 'estoy bien',
            'pregunta_al_patrocinador' => 'cómo estás',
            'detalle' => 'un abrazo',
        ]);

        // SEG-015: antes redirigía a cartas-ninio.index con un parámetro
        // cifrado que el controller no sabía leer (la línea de descifrado
        // estaba comentada) — el niño caía en un 404 justo después de
        // responder. Ahora el destino no lleva parámetro (el niño queda en
        // sesión) y debe cargar 200.
        $response->assertRedirect(route('cartas-ninio.index'));
        $response->assertSessionHas('success');
        $this->assertSame($carta->ninio->numero_child, session('responder_carta_ninio_numero_child'));
        $this->followRedirects($response)->assertOk();

        $carta->refresh();
        $this->assertSame('Respondida', $carta->estado);
        $this->assertStringContainsString('Juan', $carta->detalle);
        Storage::disk('local')->assertExists($carta->archivo_imagen_ninio);
    }

    public function test_no_se_puede_responder_dos_veces_la_misma_carta_por_web(): void
    {
        Storage::fake('local');
        $carta = $this->crearCartaPendiente();
        $carta->estado = 'Respondida';
        $carta->save();

        $response = $this->conSesionDe($carta->ninio)->post(route('cartas-ninio.guardar-contestacion'), [
            'id' => Crypt::encryptString($carta->id),
            'ninio' => Crypt::encryptString($carta->ninio->numero_child),
            'imagen' => self::IMAGEN_BASE64,
            'detalle' => 'un abrazo',
        ]);

        // CartaPolicy::responderCartaXninio deniega si ya está Respondida.
        $response->assertStatus(403);
    }

    public function test_seg006_un_ninio_no_puede_responder_la_carta_de_otro_ninio(): void
    {
        Storage::fake('local');
        $carta = $this->crearCartaPendiente();
        $comunidad = $carta->ninio->comunidad;
        $otroNinio = Ninio::create([
            'numero_child' => 'EC-'.uniqid(),
            'nombres_completos' => 'Otro niño',
            'genero' => 'Female',
            'fecha_nacimiento' => '2016-01-01',
            'edad' => 9,
            'comunidad_id' => $comunidad->id,
        ]);

        // id de carta real, pero numero_child de otro niño (intento de suplantación).
        $response = $this->post(route('cartas-ninio.guardar-contestacion'), [
            'id' => Crypt::encryptString($carta->id),
            'ninio' => Crypt::encryptString($otroNinio->numero_child),
            'imagen' => self::IMAGEN_BASE64,
            'detalle' => 'un abrazo',
        ]);

        $response->assertStatus(404);
        $this->assertSame('Enviado', $carta->fresh()->estado);
    }

    public function test_seg006_falta_el_campo_ninio_responde_404(): void
    {
        $carta = $this->crearCartaPendiente();

        $response = $this->post(route('cartas-ninio.guardar-contestacion'), [
            'id' => Crypt::encryptString($carta->id),
            'imagen' => self::IMAGEN_BASE64,
            'detalle' => 'un abrazo',
        ]);

        $response->assertStatus(404);
    }

    public function test_un_id_invalido_o_no_cifrado_responde_404(): void
    {
        $response = $this->post(route('cartas-ninio.guardar-contestacion'), [
            'id' => 'no-es-un-id-cifrado',
            'imagen' => self::IMAGEN_BASE64,
            'detalle' => 'un abrazo',
        ]);

        $response->assertStatus(404);
    }

    public function test_el_formulario_renderizado_incluye_el_ninio_cifrado(): void
    {
        $carta = $this->crearCartaPendiente();

        $response = $this->conSesionDe($carta->ninio)->get(route('cartas-ninio.ver', [
            Crypt::encryptString($carta->id),
            Crypt::encryptString($carta->ninio->numero_child),
        ]));

        $response->assertOk();
        $response->assertSee('name="ninio"', false);
    }

    /**
     * SEG-015 (parte 2): el link cifrado (el que manda la notificación por
     * correo) ya no alcanza por sí solo — si se abre sin tener la sesión de
     * ingresar() activa (p. ej. el correo quedó cacheado/extraviado y lo
     * abre otra persona), se pide el numero_child antes de mostrar nada.
     */
    public function test_abrir_el_link_de_una_carta_sin_sesion_pide_el_numero_child(): void
    {
        $carta = $this->crearCartaPendiente();

        $url = route('cartas-ninio.ver', [
            Crypt::encryptString($carta->id),
            Crypt::encryptString($carta->ninio->numero_child),
        ]);

        $response = $this->get($url);

        $response->assertRedirect(route('cartas-ninio.ingresar'));
        $response->assertDontSee('name="ninio"', false);
    }

    /**
     * Tras pedir el numero_child, no lo mandamos a la lista general — vuelve
     * directo a la carta puntual que quería ver.
     */
    public function test_tras_ingresar_el_numero_vuelve_directo_a_la_carta_que_queria_ver(): void
    {
        $carta = $this->crearCartaPendiente();

        $url = route('cartas-ninio.ver', [
            Crypt::encryptString($carta->id),
            Crypt::encryptString($carta->ninio->numero_child),
        ]);

        $this->get($url); // guarda $url como destino pendiente en sesión

        $response = $this->post(route('cartas-ninio.ingresar.store'), [
            'numero_child' => $carta->ninio->numero_child,
        ]);

        $response->assertRedirect($url);
    }

    /**
     * Mismo criterio para verArchivo() (foto/PDF adjunto) — es un recurso
     * embebido, así que sin sesión corta con 404 en vez de redirigir.
     */
    public function test_ver_archivo_sin_sesion_responde_404(): void
    {
        $carta = $this->crearCartaPendiente();

        $response = $this->get(route('cartas-ninio.ver-archivo', [
            Crypt::encryptString($carta->id),
            'foto',
        ]));

        $response->assertStatus(404);
    }

    public function test_falta_un_campo_obligatorio_redirige_con_error_de_validacion(): void
    {
        $carta = $this->crearCartaPendiente();

        $response = $this->post(route('cartas-ninio.guardar-contestacion'), [
            'id' => Crypt::encryptString($carta->id),
            'imagen' => self::IMAGEN_BASE64,
            // falta 'detalle', requerido por ResponderCartaContestacionRequest
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('detalle');
    }

    public function test_la_foto_jpg_de_la_camara_web_se_guarda_como_jpg(): void
    {
        Storage::fake('local');
        Notification::fake();
        $carta = $this->crearCartaPendiente();

        // La cámara web ahora envía JPG (antes PNG).
        $imagen = imagecreatetruecolor(4, 4);
        ob_start();
        imagejpeg($imagen);
        $jpegBase64 = 'data:image/jpeg;base64,'.base64_encode(ob_get_clean());

        $this->conSesionDe($carta->ninio)->post(route('cartas-ninio.guardar-contestacion'), [
            'id' => Crypt::encryptString($carta->id),
            'ninio' => Crypt::encryptString($carta->ninio->numero_child),
            'imagen' => $jpegBase64,
            'nombre_patrocinador' => 'Juan',
            'agradezco_por' => 'la comida',
            'te_cuento_que' => 'estoy bien',
            'pregunta_al_patrocinador' => 'cómo estás',
            'detalle' => 'un abrazo',
        ])->assertRedirect(route('cartas-ninio.index'));

        $carta->refresh();
        $this->assertMatchesRegularExpression('/\.(jpg|jpeg)$/', $carta->archivo_imagen_ninio);
        Storage::disk('local')->assertExists($carta->archivo_imagen_ninio);
    }
}
