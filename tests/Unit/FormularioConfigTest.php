<?php

namespace Tests\Unit;

use Tests\TestCase;

class FormularioConfigTest extends TestCase
{
    public function test_estado_is_an_available_field_type_with_the_32_mexican_federal_entities(): void
    {
        $tiposCampo = config('formulario.tipos_campo');
        $entidades = config('formulario.entidades_federativas');

        $this->assertArrayHasKey('ESTADO', $tiposCampo);
        $this->assertCount(32, $entidades);
        $this->assertArrayHasKey('DOMICILIO', $tiposCampo);
        $this->assertSame(
            ['codigo_postal', 'municipio', 'colonia', 'calle', 'numero'],
            array_keys(config('formulario.campos_domicilio')),
        );
        $this->assertSame(32, count(array_unique(array_values($entidades))));
    }
}
