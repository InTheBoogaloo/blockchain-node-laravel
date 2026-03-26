<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $table = 'programas';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nombre',
        'nivel_grado_id',
        'creado_en',
    ];

    public function nivelGrado()
    {
        return $this->belongsTo(NivelGrado::class, 'nivel_grado_id');
    }

    public function grados()
    {
        return $this->hasMany(Grado::class, 'programa_id');
    }
}
