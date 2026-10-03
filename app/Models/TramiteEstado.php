<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TramiteEstado extends Model
{
    protected $fillable = ['clave', 'nombre', 'orden', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
