<?php

namespace Database\Seeders;

class ConstanciaMunicipalIdentificacionUsoPredioSeeder extends LicenciaUsoSueloSeeder
{
    protected function tramiteClave(): string
    {
        return 'CONST_MUN_IDENTIFICACION_USO_PREDIO';
    }

    protected function formularioNombre(): string
    {
        return 'Constancia Municipal de Identificación y Uso del Predio';
    }

    protected function tramiteNombre(): string
    {
        return 'Constancia Municipal de Identificación y Uso del Predio';
    }

    protected function opcionesModalidad(): array
    {
        return ['Número oficial', 'Alineamiento', 'Cédula informativa de zonificación', 'Habitacional', 'Comercial', 'Industrial'];
    }
}
