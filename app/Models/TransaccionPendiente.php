<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaccionPendiente extends Model
{
    protected $table = 'transacciones_pendientes';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'persona_id',
        'institucion_id',
        'programa_id',
        'titulo_obtenido',
        'fecha_inicio',
        'fecha_fin',
        'numero_cedula',
        'titulo_tesis',
        'menciones',
        'creado_en',
    ];
}
