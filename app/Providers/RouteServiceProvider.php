<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // SEG-015: numero_child es la única credencial para identificarse como
        // un niño (tanto en /login de la API como en el ingreso web de
        // responder-cartas), y los valores reales no son de alta entropía
        // (verificado: rangos claramente secuenciales). Sin límite de
        // intentos, ambos canales eran enumerables. No cambia el modelo de
        // credenciales (eso sigue siendo SEG-012, decisión de negocio
        // pendiente) — solo hace impracticable probar miles de valores.
        RateLimiter::for('numero-child', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Fase 5 (ARQ-005): alias versionado de /api/. Mismo archivo de rutas,
            // registrado también bajo /api/v1/ con nombres de ruta prefijados
            // ('v1.') para no pisar los nombres del montaje legado (evita que
            // route('descargarCartaPdf')/route('verarchivopdfporninio'), usados en
            // ResponderCarta.php, empiecen a resolver a URLs /api/v1/... por error).
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('v1.')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });

        Route::resourceVerbs([
            'create' => 'nuevo',
            'edit' => 'editar',
        ]);

        
    }
}
