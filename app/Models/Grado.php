<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    protected $table = 'grados';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'persona_id',
        'institucion_id',
        'programa_id',
        'fecha_inicio',
        'fecha_fin',
        'titulo_obtenido',
        'numero_cedula',
        'titulo_tesis',
        'menciones',
        'hash_actual',
        'hash_anterior',
        'nonce',
        'firmado_por',
        'creado_en',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function institucion()
    {
        return $this->belongsTo(Institucion::class, 'institucion_id');
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'programa_id');
    }
}

