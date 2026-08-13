<?php

namespace App\Http\Controllers;

use App\Actions\ResponderCartaAgradecimientoAction;
use App\Actions\ResponderCartaContestacionAction;
use App\Actions\ResponderCartaIniciadaAction;
use App\Actions\ResponderCartaPresentacionMayorAction;
use App\Actions\ResponderCartaPresentacionMenorAction;
use App\Http\Requests\Cartas\ResponderCartaAgradecimientoRequest;
use App\Http\Requests\Cartas\ResponderCartaContestacionRequest;
use App\Http\Requests\Cartas\ResponderCartaIniciadaRequest;
use App\Http\Requests\Cartas\ResponderCartaPresentacionMayorRequest;
use App\Http\Requests\Cartas\ResponderCartaPresentacionMenorRequest;
use App\Models\Carta;
use App\Models\Ninio;
use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class ResponderCartaController extends Controller
{
    /**
     * SEG-015: numero_child es la única credencial de este flujo público.
     * Antes viajaba en texto plano en la URL de index() (`/cartas-ninio/{numero_child}`),
     * quedando guardado en el historial del navegador, compartible por
     * captura de pantalla, sin expiración. Ahora se valida una sola vez en
     * ingresar() y se guarda en una ventana corta de sesión (no la sesión
     * HTTP completa, un límite propio más corto) para el resto de la
     * navegación de "mis cartas".
     */
    private const SESION_NINIO = 'responder_carta_ninio_numero_child';
    private const SESION_NINIO_EXPIRA = 'responder_carta_ninio_expira';
    private const SESION_DURACION_MINUTOS = 45;

    /**
     * SEG-015 (parte 2): a dónde volver después de ingresar() cuando
     * ver()/procesarRespuesta() rebotan a alguien sin sesión — así no
     * pierde la carta puntual que quería ver (no lo mandamos a la lista
     * general a buscarla de nuevo).
     */
    private const SESION_INTENDED = 'responder_carta_intended_url';

    /**
     * Formulario público de ingreso: pide el numero_child.
     */
    public function mostrarFormularioIngreso()
    {
        return view('responder-cartas.ingresar');
    }

    /**
     * Valida el numero_child y abre la ventana de sesión (ver SESION_* arriba).
     * Limitado por el rate limiter 'numero-child' (RouteServiceProvider) —
     * los valores reales no son de alta entropía (verificado: rangos
     * claramente secuenciales), así que sin límite de intentos el número
     * era enumerable.
     */
    public function ingresar(Request $request)
    {
        $request->validate([
            'numero_child' => 'required|string|max:255',
        ]);

        $ninio = Ninio::where('numero_child', $request->numero_child)->first();

        if (!$ninio) {
            return back()->withErrors(['numero_child' => 'Número child no encontrado.'])->withInput();
        }

        Session::put(self::SESION_NINIO, $ninio->numero_child);
        Session::put(self::SESION_NINIO_EXPIRA, now()->addMinutes(self::SESION_DURACION_MINUTOS));

        $destino = Session::pull(self::SESION_INTENDED);

        return redirect($destino ?: route('cartas-ninio.index'));
    }

    /**
     * Cierra la ventana de sesión abierta por ingresar()/ver(). Pensado
     * para dispositivos compartidos (una computadora de la comunidad, un
     * teléfono familiar) donde, sin esto, la próxima persona que entre a
     * `cartas-ninio` dentro de los 45 min vería las cartas del niño
     * anterior sin volver a pedir el número.
     */
    public function salir()
    {
        Session::forget([self::SESION_NINIO, self::SESION_NINIO_EXPIRA, self::SESION_INTENDED]);
        Session::flash('success', 'Sesión cerrada.');

        return redirect()->route('cartas-ninio.ingresar');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ninio = $this->ninioDeSesion();

        if (!$ninio) {
            Session::flash('info', 'Tu sesión expiró o no ingresaste tu número. Volvé a ingresarlo.');
            return redirect()->route('cartas-ninio.ingresar');
        }

        $data = array(
            'ninio'=>$ninio,
            'cartas'=>$ninio->cartas->where('estado','Enviado')
        );
        return view('responder-cartas.index',$data);
    }

    /**
     * Resuelve el niño de la ventana de sesión abierta por ingresar(),
     * limpiándola si expiró o nunca se abrió.
     */
    private function ninioDeSesion(): ?Ninio
    {
        $numeroChild = Session::get(self::SESION_NINIO);
        $expira = Session::get(self::SESION_NINIO_EXPIRA);

        if (!$numeroChild || !$expira || now()->greaterThan($expira)) {
            Session::forget([self::SESION_NINIO, self::SESION_NINIO_EXPIRA]);
            return null;
        }

        return Ninio::where('numero_child', $numeroChild)->first();
    }

    public function ver($idcarta,$ninio_numero_child) {

        try {
            $idcarta_decrypt = Crypt::decryptString($idcarta);
            $ninio_numero_child_decrypt = Crypt::decryptString($ninio_numero_child);
            $carta=Carta::findOrFail($idcarta_decrypt);

            if($carta->ninio->numero_child!==$ninio_numero_child_decrypt){
                return abort(404);
            }

            // SEG-015 (parte 2): el link cifrado ya no alcanza por sí solo —
            // el correo/notificación que lo contiene se puede extraviar,
            // quedar cacheado, reenviarse por error, etc. Ahora hace falta
            // además una sesión activa que coincida con este niño. Si no la
            // hay, se manda a ingresar el numero_child y se recuerda esta
            // URL puntual para volver directo acá (no a la lista general).
            $sesionNinio = $this->ninioDeSesion();
            if (!$sesionNinio || $sesionNinio->numero_child !== $ninio_numero_child_decrypt) {
                Session::put(self::SESION_INTENDED, url()->current());
                Session::flash('info', 'Ingresá tu número child para ver esta carta.');
                return redirect()->route('cartas-ninio.ingresar');
            }

            // Renueva la ventana de sesión para que "Regresar a mis cartas"
            // siga funcionando sin pedir el número de nuevo.
            Session::put(self::SESION_NINIO, $carta->ninio->numero_child);
            Session::put(self::SESION_NINIO_EXPIRA, now()->addMinutes(self::SESION_DURACION_MINUTOS));

            return view('responder-cartas.todas-cartas',['carta'=>$carta,'ninio'=>$carta->ninio]);
        } catch (DecryptException $e) {
            return abort(404);
        }
    }

    public function verArchivo($idCarta,$tipo)
    {
        try {
            $carta=Carta::findOrFail(Crypt::decryptString($idCarta));

            // SEG-015 (parte 2): mismo criterio que ver() — el ID cifrado
            // del archivo no alcanza solo, exige sesión activa del dueño.
            // Es un recurso embebido (img/iframe), así que ante la falta de
            // sesión se corta con 404 en vez de redirigir a una página de
            // login (que no tendría sentido dentro de un <img>/<iframe>).
            $sesionNinio = $this->ninioDeSesion();
            if (!$sesionNinio || $sesionNinio->id !== $carta->ninio_id) {
                return abort(404);
            }

            switch ($tipo) {
                case 'foto':
                    return Storage::get($carta->archivo_imagen);
                case 'pdf':
                    return Storage::response($carta->archivo_pdf);
                default:
                    return '';
            }
        } catch (DecryptException $th) {
            return abort(404);
        }
    }

    public function guardarContestacion(ResponderCartaContestacionRequest $request, ResponderCartaContestacionAction $action)
    {
        return $this->procesarRespuesta($request, fn (Carta $carta, UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
            $carta,
            $request->nombre_patrocinador,
            $request->agradezco_por,
            $request->te_cuento_que,
            $request->pregunta_al_patrocinador,
            $request->detalle,
            $imagen,
            $fotoFamilia
        ));
    }

    public function guardarAgradecimiento(ResponderCartaAgradecimientoRequest $request, ResponderCartaAgradecimientoAction $action)
    {
        return $this->procesarRespuesta($request, fn (Carta $carta, UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
            $carta,
            $request->nombre_patrocinador,
            $request->agradezco_por,
            $request->regalo_usar_para,
            $request->te_cuento_que,
            $request->pregunta_al_patrocinador,
            $request->detalle,
            $imagen,
            $fotoFamilia
        ));
    }

    public function guardarIniciada(ResponderCartaIniciadaRequest $request, ResponderCartaIniciadaAction $action)
    {
        return $this->procesarRespuesta($request, fn (Carta $carta, UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
            $carta,
            $request->nombre_patrocinador,
            $request->te_cuento_que,
            $request->en_cactu_aprendi,
            $request->pregunta_al_patrocinador,
            $request->detalle,
            $imagen,
            $fotoFamilia
        ));
    }

    public function guardarPresentacionMayor(ResponderCartaPresentacionMayorRequest $request, ResponderCartaPresentacionMayorAction $action)
    {
        return $this->procesarRespuesta($request, fn (Carta $carta, UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
            $carta,
            $request->hola,
            $request->soy,
            $request->meDicen,
            $request->edad,
            $request->miMejorAmigo,
            $request->esMejorAmigo,
            $request->loquehago,
            $request->miSueno,
            $request->dondeAprendo,
            $request->gustaAprendes,
            $request->mePaso,
            $request->meGustaria,
            $request->miFamilia,
            $request->nuestraPro,
            $request->idioma,
            $request->lugarFavorito,
            $request->comidaTipica,
            $request->comer,
            $request->masMeGusta,
            $request->pregunta,
            $request->despedida,
            $imagen,
            $fotoFamilia
        ));
    }

    public function guardarPresentacionMenor(ResponderCartaPresentacionMenorRequest $request, ResponderCartaPresentacionMenorAction $action)
    {
        return $this->procesarRespuesta($request, fn (Carta $carta, UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
            $carta,
            $request->hola,
            $request->escribo,
            $request->mi,
            $request->queel,
            $request->cumple,
            $request->noSabe,
            $request->ademas,
            $request->leGusta,
            $request->dondeAprendo,
            $request->gustaAprendes,
            $request->mePaso,
            $request->meGustaria,
            $request->miNombre,
            $request->ysoy,
            $request->de,
            $request->mifamila,
            $request->nuestraPro,
            $request->idioma,
            $request->lugarFavorito,
            $request->comidaTipica,
            $request->ya,
            $request->comer,
            $request->masMeGusta,
            $request->pregunta,
            $request->despedida,
            $imagen,
            $fotoFamilia
        ));
    }

    /**
     * Resuelve la carta (id cifrado en el link + sesión de niño, ver
     * SEG-015), autoriza que aún no esté respondida, convierte las imágenes
     * base64 recibidas del formulario web a UploadedFile temporales, delega
     * el armado del detalle + guardado en la Action del tipo correspondiente
     * (via $ejecutarAction) y responde con el redirect que espera la vista.
     *
     * Centraliza lo que antes era guardarArchivo() — ARQ-001.
     */
    private function procesarRespuesta(Request $request, \Closure $ejecutarAction)
    {
        try {
            $carta = Carta::findOrFail(Crypt::decryptString($request->id));

            // SEG-006: el id de la carta viaja cifrado pero por sí solo no
            // prueba que quien responde sea el niño dueño; se exige además
            // el numero_child cifrado (mismo patrón que ver()).
            if (!$request->filled('ninio') || Crypt::decryptString($request->ninio) !== $carta->ninio->numero_child) {
                return abort(404);
            }

            // SEG-015 (parte 2): tampoco alcanza con poseer los campos
            // cifrados del formulario (pueden quedar cacheados en el HTML de
            // una página vieja, o replicarse fuera del formulario real) —
            // hace falta además la sesión activa abierta en ingresar()/ver().
            $sesionNinio = $this->ninioDeSesion();
            if (!$sesionNinio || $sesionNinio->id !== $carta->ninio_id) {
                return abort(404);
            }

            $this->authorize('responderCartaXninio', $carta);

            $imagen = $this->crearArchivoTemporalDesdeBase64($request->input('imagen'), $carta->id.'.png');
            $fotoFamilia = $request->foto_familia
                ? $this->crearArchivoTemporalDesdeBase64($request->foto_familia, $carta->id.'.png')
                : null;

            try {
                $carta = $ejecutarAction($carta, $imagen, $fotoFamilia);
            } finally {
                $this->eliminarSiExiste($imagen->getPathname());
                if ($fotoFamilia) {
                    $this->eliminarSiExiste($fotoFamilia->getPathname());
                }
            }

            // Quien llega acá ya demostró ser dueño de $carta->ninio (chequeo
            // de más arriba en este método) — sea que haya entrado por
            // ingresar() o por un link directo de notificación (ver() sin
            // pasar por el formulario). Se abre/renueva la ventana de sesión
            // para que index() pueda mostrarle el resto de sus cartas sin
            // pedirle el número de nuevo.
            Session::put(self::SESION_NINIO, $carta->ninio->numero_child);
            Session::put(self::SESION_NINIO_EXPIRA, now()->addMinutes(self::SESION_DURACION_MINUTOS));

            Session::flash('success', 'La respuesta a su carta ha sido enviada satisfactoriamente.');
            return redirect()->route('cartas-ninio.index');
        } catch (DecryptException $e) {
            return abort(404);
        }
    }

    private function crearArchivoTemporalDesdeBase64(string $imagenBase64, string $nombreArchivo): UploadedFile
    {
        $tempFolderPath = 'temp';
        if (!Storage::exists($tempFolderPath)) {
            Storage::makeDirectory($tempFolderPath);
        }
        $tempDirectoryPath = Storage::path($tempFolderPath);

        [, $imagenCodificada] = explode(';', $imagenBase64);
        [, $imagenCodificada] = explode(',', $imagenCodificada);
        $imagenDecodificada = base64_decode($imagenCodificada);

        $rutaTempImagen = $tempDirectoryPath.'/'.uniqid().'-'.$nombreArchivo;
        file_put_contents($rutaTempImagen, $imagenDecodificada);

        return new UploadedFile($rutaTempImagen, $nombreArchivo, 'image/png', null, true);
    }

    private function eliminarSiExiste(string $rutaArchivo): void
    {
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }
}
