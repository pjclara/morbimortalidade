<?php

namespace App\Http\Controllers;

use App\Models\Internamento;
use Illuminate\Http\Request;

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
        ])
            ->withCount('blocoOperatorios')
            ->where('data_alta', '>', '2025-01-01')
            ->paginate(10)
            ->withQueryString();

        return inertia(
            'Internamento/Index',
            [
                'items' => $internamentos
            ]
        );
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
