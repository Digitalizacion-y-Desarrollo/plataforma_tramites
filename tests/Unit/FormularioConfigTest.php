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
        $this->assertArrayHasKey('DOMICILIO_FUERA_NEZA', $tiposCampo);
        $this->assertSame('Domicilio fuera de Ciudad Nezahualcóyotl', $tiposCampo['DOMICILIO_FUERA_NEZA']);
        $this->assertArrayHasKey('DOMICILIO_USA', $tiposCampo);
        $this->assertSame(
            ['colonia', 'codigo_postal', 'municipio', 'calle', 'numero'],
            array_keys(config('formulario.campos_domicilio')),
        );
        $this->assertSame(
            ['colonia', 'codigo_postal', 'municipio', 'calle', 'numero'],
            array_keys(config('formulario.campos_domicilio_fuera_neza')),
        );
        $this->assertSame(100, config('formulario.campos_domicilio_fuera_neza.colonia.maxlength'));
        $this->assertSame(5, config('formulario.campos_domicilio_fuera_neza.codigo_postal.maxlength'));
        $this->assertSame('[0-9]{5}', config('formulario.campos_domicilio_fuera_neza.codigo_postal.pattern'));
        $this->assertSame(100, config('formulario.campos_domicilio_fuera_neza.municipio.maxlength'));
        $this->assertSame(100, config('formulario.campos_domicilio_fuera_neza.calle.maxlength'));
        $this->assertSame(50, config('formulario.campos_domicilio_fuera_neza.numero.maxlength'));
        $this->assertSame(
            ['calle', 'numero', 'ciudad', 'estado', 'codigo_postal'],
            array_keys(config('formulario.campos_domicilio_usa')),
        );
        $this->assertFalse(config('formulario.campos_domicilio_usa.numero.requerido'));
        $this->assertArrayHasKey('CA', config('formulario.estados_usa'));
        $this->assertArrayHasKey('DC', config('formulario.estados_usa'));
        $this->assertSame(32, count(array_unique(array_values($entidades))));
    }
}
