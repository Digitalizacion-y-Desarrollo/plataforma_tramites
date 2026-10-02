<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicoController extends Controller
{
    public function index(): View
    {
        return view('publico.index');
    }

    public function tramites(): View
    {
        return view('publico.tramites.index');
    }

    public function predial(): View
    {
        return view('publico.tramites.predial');
    }

    public function iniciarPredial(): View
    {
        return view('publico.tramites.predial-iniciar');
    }
}
