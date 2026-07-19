<?php

namespace App\Http\Controllers;

use App\Models\ClavienDindo;
use App\Models\Destino;
use App\Models\Equipa;
use App\Models\Internamento;
use App\Models\Origem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InternamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $internamentos = Internamento::with([
            'patient',
            'destino',
            'origem',
            'responsavel',
            'clavienDindo',
            'blocoOperatorios',
            'diagnosticoInternamentos'
        ])
            ->withCount('blocoOperatorios')
            ->where('data_alta', '>', '2025-01-01')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Internamento/Index', [
            'items' => $internamentos->through(fn($i) => [
                ...$i->toArray(),
                'destino_options' => Destino::pluck('id', 'nome'),
                'origem_options' => Origem::pluck('id', 'nome'),
                'responsavel_options' => User::pluck('id', 'name'),
                'clavien_options' => ClavienDindo::pluck('id', 'nome'),
                'equipa_options' => Equipa::pluck('id', 'nome'),
            ])
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
