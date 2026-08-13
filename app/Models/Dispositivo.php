<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    protected $fillable = [
        'ninio_id',
        'fcm_token',
    ];

    public function ninio()
    {
        return $this->belongsTo(Ninio::class);
    }
}
