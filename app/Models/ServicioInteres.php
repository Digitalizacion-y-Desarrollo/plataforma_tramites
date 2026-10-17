<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ServicioInteres extends Model
{
    use HasUuids;

    protected $table = 'servicios_interes';

    protected $fillable = ['titulo', 'descripcion', 'enlace', 'logo_storage_key'];
}
