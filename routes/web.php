<?php

use App\Http\Controllers\CartaController;
use App\Http\Controllers\ComunidadController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MisNiniosController;
use App\Http\Controllers\NinioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ResponderCartaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use App\Models\Carta;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    
    //Artisan::call('optimize:clear');
    // config cache
    //Artisan::call('config:cache');
    // migrate
    //Artisan::call('migrate');
    // Artisan::call('storage:link');
    if (Auth::check()) {
        return redirect()->route('home');
    }

    return view('welcome');
})->name('welcome');





Route::get('privacidad',[WelcomeController::class,'privacidad'])->name('privacidad');

Auth::routes(['register' => false]);




Route::middleware(['auth', 'verified'])->group(function () {
    // perfil de usuario
    Route::get('perfil', [PerfilController::class, 'perfil'])->name('perfil');
    Route::post('perfil-guardar', [PerfilController::class, 'guardarPerfil'])->name('perfil.guardar');
    Route::post('perfil-cambiar-password', [PerfilController::class, 'cambiarPassword'])->name('perfil.cambiar-password');
    Route::post('perfil-menu-responsivo', [PerfilController::class, 'menuResponsivo'])->name('perfil.menu_responsivo');

    // manual de usuario
    Route::get('manual-de-usuario',[WelcomeController::class,'manual'])->name('manual');

    Route::get('home', [HomeController::class, 'index'])->name('home');
    Route::post('validarec', [HomeController::class, 'validarec'])->name('validarec');

    // SEG-016: fuera del grupo role:ADMIN a propósito — mientras se está
    // accediendo como otro usuario, el autenticado ya no tiene ese rol y
    // necesita poder volver a su cuenta.
    Route::post('usuarios-volver', [UserController::class, 'volver'])->name('usuarios.volver');

    Route::middleware('role:'.config('app.ROLE_ADMIN'))->group(function () {
        // usuarios
        Route::resource('usuarios', UserController::class);
        Route::post('usuarios/{usuario}/acceder', [UserController::class, 'acceder'])->name('usuarios.acceder');
        // comunidad
        Route::resource('comunidad', ComunidadController::class);
        // ninios
        Route::resource('ninios', NinioController::class);
        Route::get('ninios-importar', [NinioController::class, 'importar'])->name('ninios.importar');
        Route::post('ninios-subir-importar', [NinioController::class, 'subirImportacion'])->name('ninios.subir-importar');
    });
    // gestores
    Route::resource('mis-ninios', MisNiniosController::class);
    // cartas
    Route::resource('cartas', CartaController::class);
    Route::get('cartas-ver-carta-pdf/{id}', [CartaController::class, 'verPDF'])->name('cartas.ver-carta-pdf');
    Route::get('cartas-ver-archivo/{id}/{tipo}', [CartaController::class, 'verArchivo'])->name('cartas.ver-archivo');
    Route::get('cartas-documentos/{id}', [CartaController::class, 'documentos'])->name('cartas.documentos');
    Route::get('cartas-descargar-pdf/{id}', [CartaController::class, 'descargarPdf'])->name('cartas.descargarPdf');
    
    // boletas
    Route::get('ver-boleta-archivo-imagen/{id}', [CartaController::class, 'verBoletaArchivoImagen'])->name('verBoletaArchivoImagen');
    
    
    

});

// reposnder carta niño
// SEG-015: numero_child ya no viaja como parámetro de URL (quedaba en el
// historial del navegador, sin expiración, compartible). Se ingresa una vez
// por formulario (throttled) y queda en una ventana corta de sesión — ver
// ResponderCartaController::SESION_*.
Route::get('cartas-ninio', [ResponderCartaController::class, 'mostrarFormularioIngreso'])->name('cartas-ninio.ingresar');
Route::post('cartas-ninio', [ResponderCartaController::class, 'ingresar'])->name('cartas-ninio.ingresar.store')->middleware('throttle:numero-child');
Route::get('cartas-ninio/mis-cartas', [ResponderCartaController::class, 'index'])->name('cartas-ninio.index');
Route::post('cartas-ninio/salir', [ResponderCartaController::class, 'salir'])->name('cartas-ninio.salir');
Route::get('carta-ninio-ver/{idcarta}/{numero_child}', [ResponderCartaController::class, 'ver'])->name('cartas-ninio.ver');
Route::get('carta-ninio-archivo/{id}/{tipo}', [ResponderCartaController::class, 'verArchivo'])->name('cartas-ninio.ver-archivo');
Route::post('carta-ninio-guardar-contestacion', [ResponderCartaController::class, 'guardarContestacion'])->name('cartas-ninio.guardar-contestacion');
Route::post('carta-ninio-guardar-agradecimiento', [ResponderCartaController::class, 'guardarAgradecimiento'])->name('cartas-ninio.guardar-agradecimiento');
Route::post('carta-ninio-guardar-iniciada', [ResponderCartaController::class, 'guardarIniciada'])->name('cartas-ninio.guardar-iniciada');
Route::post('cartas-ninio.guardar-presentacion-mayor', [ResponderCartaController::class, 'guardarPresentacionMayor'])->name('cartas-ninio.guardar-presentacion-mayor');
Route::post('cartas-ninio.guardar-presentacion-menor', [ResponderCartaController::class, 'guardarPresentacionMenor'])->name('cartas-ninio.guardar-presentacion-menor');



