<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Ninio extends Authenticatable
{
    use HasApiTokens,HasFactory,Notifiable;

    // DEPRECATED (Fase 7, punto 3): reemplazado por la relación dispositivos().
    // Se conserva la columna/atributo por compatibilidad de GET /user y
    // POST /login (no se escribe más desde código nuevo). Ver
    // docs/cambios/decisiones-arquitectura.md.
    protected $fillable=[
        'numero_child',
        'nombres_completos',
        'genero',
        'fecha_nacimiento',
        'edad',
        'comunidad_id',
        'estado',
        'email',
        'numero_celular',
        'fcm_token'
    ];

    public function comunidad()
    {
        return $this->belongsTo(Comunidad::class, 'comunidad_id');
    }
    public function cartas()
    {
        return $this->hasMany(Carta::class, 'ninio_id');
    }
    public function dispositivos()
    {
        return $this->hasMany(Dispositivo::class);
    }
}
