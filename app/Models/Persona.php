<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'personas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'curp',
        'correo',
        'creado_en',
    ];

    public function grados()
    {
        return $this->hasMany(Grado::class, 'persona_id');
    }
}
