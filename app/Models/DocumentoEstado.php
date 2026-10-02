<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoEstado extends Model
{
    use SoftDeletes;

    protected $table = 'documento_estados';

    protected $fillable = ['clave', 'nombre', 'descripcion', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
