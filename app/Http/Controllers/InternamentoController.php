<?php

namespace App\Http\Controllers;

use App\Models\ClavienDindo;
use App\Models\Destino;
use App\Models\Internamento;
use App\Models\Origem;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InternamentoController extends Controller
{

    public function index(Request $request)
    {
        $query = Internamento::with([
            'patient',
            'destino',
            'origem',
            'responsavel',
            'clavienDindo',
            'blocoOperatorios',
            'diagnosticoInternamentos'
        ])
        ->withCount('blocoOperatorios');

        /* -------------------------
        FILTRO: PROCESSO (patient)
        --------------------------*/
        if ($request->filled('processo')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('processo', 'like', "%{$request->processo}%");
            });
        }

        /* -------------------------
        FILTRO: DATA ENTRADA (intervalo)
        --------------------------*/
        if ($request->filled('data_entrada_de')) {
            $query->whereDate('data_entrada', '>=', $request->data_entrada_de);
        }

        if ($request->filled('data_entrada_ate')) {
            $query->whereDate('data_entrada', '<=', $request->data_entrada_ate);
        }

        /* -------------------------
        FILTRO: DESTINO
        --------------------------*/
        if ($request->filled('destino_id')) {
            $query->where('destino_id', $request->destino_id);
        }

        /* -------------------------
        FILTRO: ORIGEM
        --------------------------*/
        if ($request->filled('origem_id')) {
            $query->where('origem_id', $request->origem_id);
        }

        /* -------------------------
        FILTRO: RESPONSÁVEL
        --------------------------*/
        if ($request->filled('responsavel_id')) {
            $query->where('responsavel_id', $request->responsavel_id);
        }

        /* -------------------------
        FILTRO: CLAVIEN-DINDO
        --------------------------*/
        if ($request->filled('clavien_dindo_id')) {
            $query->where('clavien_dindo_id', $request->clavien_dindo_id);
        }

        /* -------------------------
        FILTRO: FALECIDO (boolean)
        --------------------------*/
        if ($request->filled('falecido')) {
            $query->where('falecido', $request->falecido);
        }

        /* -------------------------
        FILTRO FIXO (o teu)
        --------------------------*/
        $query->where('data_alta', '>', '2025-01-01');

        /* -------------------------
        PAGINAÇÃO
        --------------------------*/
        $internamentos = $query->paginate(20)->withQueryString();

        /* -------------------------
        OPTIONS PARA SELECTS
        --------------------------*/
        $destinoOptions = Destino::pluck('id', 'nome');
        $origemOptions = Origem::pluck('id', 'nome');
        $responsavelOptions = User::pluck('id', 'name');
        $clavienOptions = ClavienDindo::pluck('id', 'nome');

        return Inertia::render('Internamento/Index', [
            'items' => $internamentos->through(fn($i) => [
                ...$i->toArray(),
                'destino_options' => $destinoOptions,
                'origem_options' => $origemOptions,
                'responsavel_options' => $responsavelOptions,
                'clavien_options' => $clavienOptions,
            ]),

            'filters' => $request->only([
                'processo',
                'data_entrada_de',
                'data_entrada_ate',
                'destino_id',
                'origem_id',
                'responsavel_id',
                'clavien_dindo_id',
                'falecido'
            ]),

            'destino_options' => $destinoOptions,
            'origem_options' => $origemOptions,
            'responsavel_options' => $responsavelOptions,
            'clavien_options' => $clavienOptions
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
        $internamento = Internamento::findOrFail($id);

        $validatedData = $request->validate([
            'destino_id' => 'nullable|exists:destinos,id',
            'origem_id' => 'nullable|exists:origems,id',
            'responsavel_id' => 'nullable|exists:users,id',
            'clavien_dindo_id' => 'nullable|exists:clavien_dindos,id',
            'falecido' => 'nullable|boolean',
            'observacoes' => 'nullable|string|max:1000',
        ]);


        $internamento->update($validatedData);

        return redirect()->back()->with('success', 'Internamento atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
