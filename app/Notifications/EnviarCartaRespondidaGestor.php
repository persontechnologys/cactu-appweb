<?php

namespace App\Notifications;

use App\Models\Carta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnviarCartaRespondidaGestor extends Notification implements ShouldQueue
{
    use Queueable;

    protected Carta $carta;
    /**
     * Create a new notification instance.
     */
    public function __construct($carta)
    {
        $this->carta=$carta;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Nueva carta contestada')
                    ->line($this->carta->ninio->nombres_completos.' a contestado a la carta de '.$this->carta->tipo)
                    ->action('Ver carta contestada', route('cartas.show',$this->carta->id))
                    ->line('Gracias por tu atención!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
