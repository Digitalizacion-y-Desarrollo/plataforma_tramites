<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CanalAyuda extends Model
{
    use HasUuids;

    protected $table = 'canales_ayuda';

    protected $fillable = ['preguntas_frecuentes', 'horario_atencion', 'telefono_contacto', 'email_contacto', 'redes_sociales'];

    protected function casts(): array
    {
        return ['preguntas_frecuentes' => 'array', 'redes_sociales' => 'array'];
    }
}
