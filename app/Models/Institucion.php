<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'instituciones';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nombre',
        'pais',
        'estado',
        'creado_en',
    ];

    public function grados()
    {
        return $this->hasMany(Grado::class, 'institucion_id');
    }
}
