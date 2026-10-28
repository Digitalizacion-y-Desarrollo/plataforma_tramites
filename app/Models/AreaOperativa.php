<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AreaOperativa extends Model
{
    use HasUuids;

    protected $table = 'areas_operativas';

    protected $fillable = [
        'accesos_departamento_id',
        'nombre_snapshot',
        'siglas_snapshot',
        'activa',
    ];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }
}
