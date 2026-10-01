<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoSolicitud extends Model
{
    use SoftDeletes;

    protected $table = 'solicitud_estados';

    protected $fillable = ['clave', 'nombre', 'descripcion', 'orden', 'es_final', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'es_final' => 'boolean'];
    }
}
