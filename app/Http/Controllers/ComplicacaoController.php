<?php

namespace App\Http\Controllers;

use App\Models\Complicacao;
use App\Models\GrupoComplicacao;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ComplicacaoController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Complicacoes', [
            'complicacoes' => Complicacao::with('grupoComplicacao')->orderBy('nome')->get(),
            'grupos' => GrupoComplicacao::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'grupo_complicacao_id' => 'nullable|exists:grupo_complicacaos,id',
        ]);

        Complicacao::create($data);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Complicação criada',
            'description' => 'A complicação foi criada com sucesso.',
        ]);
    }

    public function update(Request $request, Complicacao $complicacao)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'grupo_complicacao_id' => 'nullable|exists:grupo_complicacaos,id',
        ]);

        $complicacao->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Complicação atualizada',
            'description' => 'A complicação foi atualizada com sucesso.',
        ]);
    }

    public function destroy(Complicacao $complicacao)
    {
        $complicacao->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Complicação eliminada',
            'description' => 'A complicação foi eliminada com sucesso.',
        ]);
    }
}
