<?php

namespace Database\Seeders;

use App\Models\CampoCondicion;
use App\Models\FormularioCampo;
use App\Models\FormularioSeccion;
use Illuminate\Support\Str;

class LicenciaUnicaConstruccionSeeder extends LicenciaUsoSueloSeeder
{
    protected function tramiteClave(): string
    {
        return 'LIC_UNICA_CONSTRUCCION';
    }

    protected function formularioNombre(): string
    {
        return 'Licencia Única de Construcción';
    }

    protected function tramiteNombre(): string
    {
        return 'Licencia Única de Construcción';
    }

    protected function mensajeExistenciaParcial(): string
    {
        return 'La Licencia Única de Construcción existe parcialmente; revisa los registros antes de ejecutar el seeder.';
    }

    protected function opcionesModalidad(): array
    {
        return [
            'Licencia de construcción.',
            'Licencia de ampliación.',
            'Licencia de remodelación o modificación.',
            'Licencia de demolición, excavación o relleno de tierra.',
            'Barda (más de 10 m por 2.20 m).',
            'Regularización.',
            'Terminación de obra.',
            'Cambio a régimen en condominio.',
            'Prórroga.',
            'Otro',
        ];
    }

    protected function sembrarCampos(string $versionId): array
    {
        $campos = parent::sembrarCampos($versionId);

        $seccion = FormularioSeccion::query()
            ->where('formulario_version_id', $versionId)
            ->where('clave', 'modalidad')
            ->firstOrFail();

        foreach (['fecha_solicitud', 'giro_solicitado', 'protesta_verdad'] as $clave) {
            $campos[$clave]->increment('orden');
        }

        $campos['especifica_otro_tipo_licencia'] = FormularioCampo::create([
            'id' => (string) Str::uuid(),
            'seccion_id' => $seccion->id,
            'clave' => 'especifica_otro_tipo_licencia',
            'etiqueta' => 'Especifica el otro tipo de licencia',
            'tipo' => 'TEXT',
            'requerido' => true,
            'orden' => 2,
            'configuracion' => null,
            'activo' => true,
        ]);

        return $campos;
    }

    protected function sembrarCondiciones(array $c): void
    {
        parent::sembrarCondiciones($c);

        CampoCondicion::create([
            'id' => (string) Str::uuid(),
            'campo_destino_id' => $c['especifica_otro_tipo_licencia']->id,
            'campo_origen_id' => $c['uso_giro']->id,
            'operador' => 'EQUALS',
            'valor_comparacion' => ['valor' => 'Otro'],
            'accion' => 'SHOW',
        ]);
    }
}
