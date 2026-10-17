<?php

namespace Tests\Feature;

use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AccesibilidadViewsTest extends TestCase
{
    public function test_public_layout_has_accessibility_controls(): void
    {
        $this->withoutVite();

        $html = view('layouts.publico')->render();

        $this->assertStringContainsString('data-accessibility-open', $html);
        $this->assertStringContainsString('id="accessibility-dialog"', $html);
        $this->assertStringContainsString('name="accessibility-text"', $html);
        $this->assertStringContainsString('name="accessibility-contrast"', $html);
        $this->assertStringContainsString('name="accessibility-motion"', $html);
        $this->assertStringContainsString('data-accessibility-reset', $html);
    }

    public function test_administration_layout_uses_the_same_accessibility_panel(): void
    {
        $this->withoutVite();

        $html = view('layouts.app', ['errors' => new ViewErrorBag])->render();

        $this->assertStringContainsString('aria-label="Abrir opciones de accesibilidad"', $html);
        $this->assertStringContainsString('id="accessibility-dialog"', $html);
        $this->assertStringContainsString('data-accessibility-reset', $html);
    }
}
