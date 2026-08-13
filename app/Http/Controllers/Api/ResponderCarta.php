<?php

namespace App\Http\Controllers\Api;

use App\Actions\ResponderCartaAgradecimientoAction;
use App\Actions\ResponderCartaContestacionAction;
use App\Actions\ResponderCartaIniciadaAction;
use App\Actions\ResponderCartaPresentacionMayorAction;
use App\Actions\ResponderCartaPresentacionMenorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cartas\ResponderCartaAgradecimientoRequest;
use App\Http\Requests\Cartas\ResponderCartaContestacionRequest;
use App\Http\Requests\Cartas\ResponderCartaIniciadaRequest;
use App\Http\Requests\Cartas\ResponderCartaPresentacionMayorRequest;
use App\Http\Requests\Cartas\ResponderCartaPresentacionMenorRequest;
use App\Http\Resources\CartaListaResource;
use App\Models\Carta;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ResponderCarta extends Controller
{

    public function listaCartas (Request $request) {
        // se usa el niño resuelto por el token Sanctum, no el userId enviado
        // por el cliente (ver SEG-004): de lo contrario cualquier niño
        // autenticado podía leer el listado de cartas de otro niño.
        $ninio = $request->user();


        $cartas = $ninio->cartas()
            ->where('eliminado',false)
            ->orderByRaw("CASE WHEN estado = 'Enviado' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => CartaListaResource::collection($cartas)]);
    }

    public function responderCartaPresentacionMayor(ResponderCartaPresentacionMayorRequest $request, ResponderCartaPresentacionMayorAction $action) {

        return $this->procesarRespuesta($request, fn (Carta $carta, ?UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
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

    public function responderCartaPresentacionMenor(ResponderCartaPresentacionMenorRequest $request, ResponderCartaPresentacionMenorAction $action) {

        return $this->procesarRespuesta($request, fn (Carta $carta, ?UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
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

    public function responderContestacion(ResponderCartaContestacionRequest $request, ResponderCartaContestacionAction $action)
    {
        return $this->procesarRespuesta($request, fn (Carta $carta, ?UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
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

    public function responderAgradecimineto(ResponderCartaAgradecimientoRequest $request, ResponderCartaAgradecimientoAction $action) {
        return $this->procesarRespuesta($request, fn (Carta $carta, ?UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
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

    public function responderIniciada(ResponderCartaIniciadaRequest $request, ResponderCartaIniciadaAction $action) {
        return $this->procesarRespuesta($request, fn (Carta $carta, ?UploadedFile $imagen, ?UploadedFile $fotoFamilia) => $action->ejecutar(
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

    /**
     * Resuelve la carta por id plano contra el niño autenticado por Sanctum
     * (SEG-005), corta temprano si ya fue respondida (sin persistir, igual
     * que antes), normaliza imagen/foto_familia a UploadedFile válido o null
     * y delega el armado del detalle + guardado en la Action del tipo
     * correspondiente (via $ejecutarAction).
     *
     * Centraliza lo que antes era guardarCarta() — ARQ-001.
     */
    private function procesarRespuesta(Request $request, \Closure $ejecutarAction)
    {
        try {
            $carta = Carta::findOrFail($request->id);

            // la carta debe pertenecer al niño autenticado (ver SEG-005)
            if ($carta->ninio_id !== $request->user()->id) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            if ($carta->estado == 'Respondida') {
                return response()->json(['info' => 'Carta ya fue respondida']);
            }

            $imagen = ($request->hasFile('imagen') && $request->file('imagen')->isValid())
                ? $request->file('imagen')
                : null;
            $fotoFamilia = ($request->hasFile('foto_familia') && $request->file('foto_familia')->isValid())
                ? $request->file('foto_familia')
                : null;

            $ejecutarAction($carta, $imagen, $fotoFamilia);

            return response()->json(['success' => 'Carta resgitrado exitosamente']);
        } catch (\Throwable $th) {
            Log::error('Error al guardar carta: '.$th->getMessage());
            return response()->json(['error' => 'Carta no registrado vuelva intentar']);
        }
    }

    public function enviarMensaje() {
        return response()->json(['success'=>'Carta registrado exitosamente']);
    }


    public function eliminarCarta(Request $request) {
        $request->validate([
            'id'=>'required'
        ]);

        $carta=Carta::findOrFail($request->id);

        // la carta debe pertenecer al niño autenticado (ver SEG-005)
        if($carta->ninio_id!==$request->user()->id){
            return response()->json(['error'=>'No autorizado'],403);
        }

        $carta->eliminado=true;
        $carta->save();
        return response()->json(['success'=>'Carta eliminado exitosamente.!']);
    }

}
