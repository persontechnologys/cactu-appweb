<?php

use App\Actions\RegistrarDispositivoAction;
use App\Http\Controllers\Api\ResponderCarta;
use App\Http\Controllers\Api\TokenFcmController;
use App\Http\Controllers\WelcomeController;
use App\Models\Carta;
use App\Models\Ninio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::post('/login', function (Request $request) {
    // SEG-015: ver RouteServiceProvider — numero_child es la única
    // credencial y no es de alta entropía, así que se limita el ritmo de
    // intentos.

    // SEG-013: fuera del try/catch a propósito, para que Laravel maneje
    // ValidationException de forma estándar (422 con {errors:{...}}) en vez
    // de que la capture el catch(\Throwable) de abajo y devuelva un string
    // suelto con HTTP 200.
    $request->validate([
        'numero_child' => 'required|string|max:255',
        'fcm_token' => 'nullable|string',
    ]);

    try {
        $ninio = Ninio::where('numero_child', $request->numero_child)->first();

        if (!$ninio) {
            return response()->json(['errors' => ['email' => ['Número child no existe.!.']]], 422);
        }else{
            $tk = $ninio->createToken((string)$ninio->numeroChild)->plainTextToken;
            // SEG-014: antes se asignaba $request->fcm_token sin condición, así
            // que un login que no enviara el campo borraba el token FCM ya
            // guardado (p. ej. reintentos de red del cliente sin el valor
            // cacheado a mano). Solo se registra cuando el cliente sí lo envía.
            if ($request->filled('fcm_token')) {
                (new RegistrarDispositivoAction())->registrar($ninio, $request->fcm_token);
            }
            $data = array(
                'message' => 'ok',
                'id' => $ninio->id,
                'numero_child' => $ninio->numero_child,
                'nombres' => $ninio->nombres_completos,
                'token' => $tk,
                'roles_permisos' => []
            );
            return response()->json($data);
        }
    } catch (\Throwable $th) {
        // SEG-013: antes se devolvía $th->getMessage() directo al cliente.
        Log::error('Error al iniciar sesión: '.$th->getMessage());
        return response()->json(['error' => 'No se pudo iniciar sesión, intente nuevamente']);
    }
})->middleware('throttle:numero-child');


Route::get('ver-archivo-pdf-por-ninio/{id}',[WelcomeController::class,'pdfninio'])->name('verarchivopdfporninio');
Route::get('dcpdf/{id}',[WelcomeController::class,'descargarCartaPdf'])->name('descargarCartaPdf');


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/listar-mis-cartas',[ResponderCarta::class,'listaCartas']);
    Route::post('/responder-carta-contestacion', [ResponderCarta::class,'responderContestacion']);
    Route::post('/responder-carta-agradecimiento', [ResponderCarta::class,'responderAgradecimineto']);
    Route::post('/responder-carta-iniciada', [ResponderCarta::class,'responderIniciada']);
    Route::post('/responder-carta-presentacion-menores',[ResponderCarta::class,'responderCartaPresentacionMenor'] );
    Route::post('/responder-carta-presentacion-mayores',[ResponderCarta::class,'responderCartaPresentacionMayor'] );
    Route::post('/enviar-mensaje',[ResponderCarta::class,'enviarMensaje'] );
    Route::post('/eliminar-carta',[ResponderCarta::class,'eliminarCarta'] );
    Route::post('/actualizar-token-fcm', [TokenFcmController::class, 'actualizar']);
    Route::post('/eliminar-token-fcm', [TokenFcmController::class, 'eliminar']);

});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
