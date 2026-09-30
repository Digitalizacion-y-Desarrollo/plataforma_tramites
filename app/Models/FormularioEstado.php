<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormularioEstado extends Model
{
    protected $fillable = ['clave', 'nombre', 'descripcion', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
