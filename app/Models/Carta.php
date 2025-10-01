<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Carta extends Model
{
    use HasFactory;
    protected $casts = [
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'fecha_respondida' => 'datetime', // <<— añade esto
    ];

    // crea un cast o metodo para colores por tipo de carta
    public function getColorAttribute()
    {
        return match ($this->tipo) {
            'Contestación' => 'primary',
            'Agradecimiento' => 'purple',
            'Presentación' => 'info',
            'Iniciada' => 'pink',
            default => 'secondary',
        };
    }

    protected $fillable=[
        'tipo',
        'asunto',
        'detalle',
        'archivo_imagen',
        'archivo_pdf',
        'archivo_imagen_ninio',
        'ninio_id',
        'user_id',
        'comunidad_id',
        'estado', //Enviado, Respondida
    ];
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
             $model->user_id=Auth::id();
        });
        self::updating(function($model){
            // $model->user_id=Auth::id();
        });
        self::created(function($model){
         });
    }

    public function ninio()
    {
        return $this->belongsTo(Ninio::class, 'ninio_id');
    }

    public function gestor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function comunidad(){
        return $this->belongsTo(Comunidad::class,'comunidad_id');
    }

    // archivo_imagen_ninio_link
    public function getArchivoImagenNinioLinkAttribute()
    {
        if($this->archivo_imagen_ninio && Storage::exists($this->archivo_imagen_ninio)){
            return Storage::url($this->archivo_imagen_ninio) ;
        }
        return null;
    }

    // archivo_imagen_link
    public function getArchivoImagenLinkAttribute()
    {
        if($this->archivo_imagen && Storage::exists($this->archivo_imagen)){
            return Storage::url($this->archivo_imagen) ;
        }
        return null;
    }
    
    // archivo_familia_ninio_link
    public function getArchivoFamiliaNinioLinkAttribute()
    {
        if($this->archivo_familia_ninio && Storage::exists($this->archivo_familia_ninio)){
            return Storage::url($this->archivo_familia_ninio) ;
        }
        return null;
    }

    // una carta tiene boletas

    public function boletas()
    {
        return $this->hasMany(Boleta::class);
    }

    /**
     * Obtener el icono según el tipo de carta
     */
    public function getIconoAttribute()
    {
        return match ($this->tipo) {
            'Contestación' => 'ph-arrow-bend-up-left',
            'Agradecimiento' => 'ph-heart',
            'Presentación' => 'ph-user-circle',
            'Iniciada' => 'ph-play-circle',
            default => 'ph-envelope-simple',
        };
    }

    /**
     * Obtener el estado formateado con color
     */
    public function getEstadoColorAttribute()
    {
        return match ($this->estado) {
            'Respondida' => 'success',
            'Enviado' => 'warning',
            'Pendiente' => 'danger',
            default => 'secondary',
        };
    }
    

}
