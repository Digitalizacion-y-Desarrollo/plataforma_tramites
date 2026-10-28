<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionGeneral extends Model
{
    use HasUuids;

    protected $table = 'configuraciones_generales';

    protected $fillable = ['origen_ficha_pago', 'portada_storage_key'];
}
