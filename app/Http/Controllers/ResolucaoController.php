<?php

namespace App\Http\Controllers;

use App\Models\ClavienDindo;
use App\Models\Resolucao;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResolucaoController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Resolucoes', [
            'resolucoes' => Resolucao::with('clavienDindo')->orderBy('nome')->get(),
            'clavienDindos' => ClavienDindo::orderBy('nome')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'clavien_dindo_id' => 'nullable|exists:clavien_dindos,id',
        ]);

        Resolucao::create($data);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Resolução criada',
            'description' => 'A resolução foi criada com sucesso.',
        ]);
    }

    public function update(Request $request, Resolucao $resolucao)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'clavien_dindo_id' => 'nullable|exists:clavien_dindos,id',
        ]);

        $resolucao->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Resolução atualizada',
            'description' => 'A resolução foi atualizada com sucesso.',
        ]);
    }

    public function destroy(Resolucao $resolucao)
    {
        $resolucao->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Resolução eliminada',
            'description' => 'A resolução foi eliminada com sucesso.',
        ]);
    }
}
