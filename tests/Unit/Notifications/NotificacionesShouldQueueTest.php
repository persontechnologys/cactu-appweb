<?php

namespace Tests\Unit\Notifications;

use App\Notifications\EnviarCartaRespondidaGestor;
use App\Notifications\EnviarNotificacionCartaNueva;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

/**
 * PERF-001: ambas notificaciones deben encolarse en vez de bloquear el
 * request HTTP. Antes de esta corrección importaban ShouldQueue sin
 * implementarla, por lo que se enviaban de forma síncrona pese a que el
 * driver de cola fuera distinto de `sync`.
 */
class NotificacionesShouldQueueTest extends TestCase
{
    public function test_enviar_carta_respondida_gestor_implementa_should_queue(): void
    {
        $this->assertContains(ShouldQueue::class, class_implements(EnviarCartaRespondidaGestor::class));
    }

    public function test_enviar_notificacion_carta_nueva_implementa_should_queue(): void
    {
        $this->assertContains(ShouldQueue::class, class_implements(EnviarNotificacionCartaNueva::class));
    }
}
