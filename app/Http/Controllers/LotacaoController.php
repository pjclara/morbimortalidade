<?php

namespace App\Http\Controllers;

use App\Models\Lotacao;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LotacaoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('Admin/Lotacoes', [
            'lotacoes' => Lotacao::orderByDesc('data_inicio')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'numero_camas' => 'required|integer|min:1',
            'observacoes' => 'nullable|string|max:255',
        ]);

        Lotacao::create($validated);

        return back()->with('success', 'Lotação criada.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lotacao $lotacao)
    {
        $validated = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after_or_equal:data_inicio',
            'numero_camas' => 'required|integer|min:1',
            'observacoes' => 'nullable|string|max:255',
        ]);

        $lotacao->update($validated);

        return back()->with('success', 'Lotação atualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lotacao $lotacao)
    {
        $lotacao->delete();

        return back()->with('success', 'Lotação removida.');
    }
}
