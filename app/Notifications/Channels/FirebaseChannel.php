<?php

namespace App\Notifications\Channels;

use App\Services\FirebaseService;
use Illuminate\Notifications\Notification;

/**
 * Reemplaza el canal 'firebase' que aportaba kutia-software-company/larafirebase
 * (retirado en FIREBASE-001 — usaba la API legacy de FCM, decomisionada por
 * Google en 2024). Delega el envío real en FirebaseService (FCM HTTP v1).
 */
class FirebaseChannel
{
    public function __construct(private FirebaseService $firebase)
    {
    }

    public function send($notifiable, Notification $notification): void
    {
        ['titulo' => $titulo, 'cuerpo' => $cuerpo] = $notification->toFirebase($notifiable);

        $this->firebase->enviarNotificacion($notifiable, $titulo, $cuerpo);
    }
}
