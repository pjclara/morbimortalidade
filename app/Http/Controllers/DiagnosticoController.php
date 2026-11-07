<?php

namespace App\Http\Controllers;

use App\Models\Icd10CM;
use Illuminate\Http\Request;

class DiagnosticoController extends Controller
{
    /**
     * Pesquisa ICD-10-CM por código ou descrição (`?q=`), usada pelo
     * seletor de comorbilidades no separador "Complicações" do internamento
     * — a tabela `icd10_cm` tem ~99 mil linhas, por isso é sempre pesquisa
     * no servidor, nunca uma lista pré-carregada no frontend.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $resultados = Icd10CM::query()
            ->where('valid', true)
            ->where(fn ($query) => $query->where('code', 'like', "{$q}%")->orWhere('description', 'like', "%{$q}%"))
            ->orderByRaw('code like ? desc', ["{$q}%"])
            ->limit(20)
            ->get(['code', 'description']);

        return response()->json($resultados);
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
