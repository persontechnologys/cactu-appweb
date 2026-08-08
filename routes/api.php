<?php

use App\Http\Controllers\Api\ResponderCarta;
use App\Http\Controllers\WelcomeController;
use App\Models\Carta;
use App\Models\Ninio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::post('/login', function (Request $request) {
    
    try {
        $request->validate([
            'numero_child' => 'required|string|max:255'
        ]);

        $ninio = Ninio::where('numero_child', $request->numero_child)->first();

        if (!$ninio) {
            return response()->json(['errors' => ['email' => ['Número child no existe.!.']]], 422);
        }else{
            $tk = $ninio->createToken((string)$ninio->numeroChild)->plainTextToken;
            $ninio->fcm_token=$request->fcm_token;
            $ninio->save();
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
        return response()->json($th->getMessage());
    }
});


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
    
});


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
