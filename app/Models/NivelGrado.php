<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NivelGrado extends Model
{
    protected $table = 'niveles_grado';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nombre',
    ];

    public function programas()
    {
        return $this->hasMany(Programa::class, 'nivel_grado_id');
    }
}
