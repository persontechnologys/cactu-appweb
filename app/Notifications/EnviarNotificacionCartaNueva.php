<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class EnviarNotificacionCartaNueva extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Reintentos ante errores transitorios de Firebase (red, cuota, servidor
     * caído) — FIREBASE-001. Un token inválido no llega a agotar reintentos:
     * FirebaseService lo detecta (NotFound) y limpia sin relanzar la excepción.
     */
    public $tries = 3;
    public $backoff = 30;

    protected $carta;
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
        $ninio=$this->carta->ninio;
        $data = array();

        if($ninio->email){
            array_push($data,'mail');
        }
        if($ninio->dispositivos()->exists()){
            array_push($data,'firebase');
        }
        return $data;
        
        
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $ninio=$this->carta->ninio;
        $es=$ninio->genero==='Female'?'Estimada Niña':'Estimado Niño';
        $url=route('cartas-ninio.ver',['idcarta'=>Crypt::encryptString($this->carta->id),'numero_child'=>Crypt::encryptString($ninio->numero_child)]);
        return (new MailMessage)
                    ->subject('Tiene nueva carta de '.$this->carta->tipo)
                    ->line('CACTU piensa en ti y te ayuda a recibir tus carta de tu patrocinador!')
                    ->line($es.' tiene una nueva carta, porfavor ingresa al siguente enlace para responder.')
                    ->action('Responder carta de '.$this->carta->tipo, $url)
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
    /**
     * @return array{titulo: string, cuerpo: string}
     */
    public function toFirebase($notifiable): array
    {
        return [
            'titulo' => 'Nueva carta',
            'cuerpo' => 'Tiene una nueva carta de '.$this->carta->tipo.' por responder.',
        ];
    }
}
