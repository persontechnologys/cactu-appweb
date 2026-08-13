<?php

namespace App\Providers;

use App\Http\Clases\LaravelValidatorEc;
use App\Notifications\Channels\FirebaseChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // para validar identificacion de ecuador
        Validator::resolver(function ($translator, $data, $rules, $messages) {
            return new  LaravelValidatorEc($translator, $data, $rules, $messages);
        });

        // FIREBASE-001: canal 'firebase' propio, sobre FCM HTTP v1 (ver FirebaseChannel/FirebaseService).
        $this->app->make(ChannelManager::class)->extend('firebase', function ($app) {
            return $app->make(FirebaseChannel::class);
        });
    }
}
