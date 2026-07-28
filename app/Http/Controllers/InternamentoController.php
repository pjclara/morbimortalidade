<?php

namespace App\Http\Controllers;

use App\Models\ClavienDindo;
use App\Models\Complicacao;
use App\Models\Destino;
use App\Models\Internamento;
use App\Models\Origem;
use App\Models\Resolucao;
use App\Models\User;
use App\Services\BlocoOperatorioImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InternamentoController extends Controller
{

    public function index(Request $request)
    {
        $query = Internamento::with([
            'patient',
            'diagnosticos',
            'origem',
            'destino',
            'clavienDindo',
            'blocoOperatorios',
            'responsavel',
            'complicacaoInternamentos'
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
        // Datas vindas do request
        $de = $request->input('data_entrada_de');
        $ate = $request->input('data_entrada_ate');

        // Se o utilizador NÃO enviou datas → usar intervalo padrão
        if (!$de || !$ate) {
            $de = '2025-09-01';
            $ate = '2025-09-30';
        }

        // Garantir que a data inicial é menor que a final
        if ($de > $ate) {
            [$de, $ate] = [$ate, $de]; // troca automática
        }

        // Aplicar filtro
        $query->whereBetween('data_saida', [$de, $ate]);

        // Enviar os filtros para o frontend
        $filtros = [
            'data_entrada_de' => $de,
            'data_entrada_ate' => $ate,
        ];


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
        PAGINAÇÃO
        --------------------------*/
        $internamentos = $query->orderby('data_saida')->paginate(20)->withQueryString();

        /* -------------------------
        OPTIONS PARA SELECTS
        --------------------------*/
        $destinoOptions = Destino::pluck('id', 'nome');
        $origemOptions = Origem::pluck('id', 'nome');
        $responsavelOptions = User::where('ativo', true)->pluck('id', 'name');
        $clavienOptions = ClavienDindo::pluck('id', 'nome');

        // filtros fixos vindos do request
        $filtersFixos = $request->only([
            'processo',
            'destino_id',
            'origem_id',
            'responsavel_id',
            'clavien_dindo_id',
            'falecido'
        ]);

        // filtros dinâmicos (ex: datas, tipo_filtro, bloco, etc.)
        $filters = array_merge($filtersFixos, $filtros);

        $complicacoesList = Complicacao::orderBy('nome', 'asc')->pluck('id', 'nome');
        $resolucoesList = Resolucao::orderBy('nome', 'asc')->pluck('id', 'nome');


        return Inertia::render('Internamento/Index', [
            'items' => $internamentos->through(fn($i) => [
                ...$i->toArray(),
                'destino_options' => $destinoOptions,
                'origem_options' => $origemOptions,
                'responsavel_options' => $responsavelOptions,
                'clavien_options' => $clavienOptions,
                'complicacao_options' => $complicacoesList,
                'resolucao_options' => $resolucoesList
            ]),

            'filters' => $filters,

            'destino_options' => $destinoOptions,
            'origem_options' => $origemOptions,
            'responsavel_options' => $responsavelOptions,
            'clavien_options' => $clavienOptions,

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
            'data_alta' => [
                'nullable',
                'date',
                Rule::date()->beforeOrEqual($internamento->data_saida),
            ],
            'origem_id' => 'nullable|exists:origems,id',
            'responsavel_id' => 'nullable|exists:users,id',
            'clavien_dindo_id' => 'nullable|exists:clavien_dindos,id',
            'falecido' => 'nullable|boolean',
            'observacoes' => 'nullable|string|max:1000',
            'complicacao_internamentos' => 'nullable|array',
            'complicacao_internamentos.*.id' => 'nullable|exists:complicacao_internamento,id',
            'complicacao_internamentos.*.complicacao_id' => 'nullable|exists:complicacaos,id',
            'complicacao_internamentos.*.resolucaos' => 'nullable|array',
            'complicacao_internamentos.*.resolucaos.*.id' => 'nullable|exists:resolucaos,id',
        ]);

        $complicacaoInternamentosData = $validatedData['complicacao_internamentos'] ?? [];
        unset($validatedData['complicacao_internamentos']);

        DB::transaction(function () use ($internamento, $validatedData, $complicacaoInternamentosData) {
            $internamento->update($validatedData);

            $incomingIds = [];

            foreach ($complicacaoInternamentosData as $ciData) {
                $resolvedIds = array_values(array_filter(array_map(fn($r) => $r['id'] ?? null, $ciData['resolucaos'] ?? [])));

                if (!empty($ciData['id'])) {
                    $complicacaoInternamento = $internamento->complicacaoInternamentos()->where('id', $ciData['id'])->first();

                    if ($complicacaoInternamento) {
                        if ($ciData['complicacao_id']) {
                            $complicacaoInternamento->update([
                                'complicacao_id' => $ciData['complicacao_id'],
                            ]);
                            $complicacaoInternamento->resolucaos()->sync($resolvedIds);
                            $incomingIds[] = $complicacaoInternamento->id;
                        } else {
                            $complicacaoInternamento->resolucaos()->detach();
                            $complicacaoInternamento->delete();
                        }
                    }
                } elseif (!empty($ciData['complicacao_id'])) {
                    $complicacaoInternamento = $internamento->complicacaoInternamentos()->create([
                        'complicacao_id' => $ciData['complicacao_id'],
                    ]);
                    $complicacaoInternamento->resolucaos()->sync($resolvedIds);
                    $incomingIds[] = $complicacaoInternamento->id;
                }
            }

            if (count($incomingIds) > 0) {
                $internamento->complicacaoInternamentos()->whereNotIn('id', $incomingIds)->get()->each(function ($ci) {
                    $ci->resolucaos()->detach();
                    $ci->delete();
                });
            } else {
                $internamento->complicacaoInternamentos()->get()->each(function ($ci) {
                    $ci->resolucaos()->detach();
                    $ci->delete();
                });
            }
            // ---------------------------------------------------------
            // Atualizar Clavien-Dindo do internamento
            // ---------------------------------------------------------

            $internamento->load('complicacaoInternamentos.resolucaos');
            $maxClavien = null;

            foreach ($internamento->complicacaoInternamentos as $ci) {

                foreach ($ci->resolucaos()->get() as $res) {

                    if (
                        $res->clavien_dindo_id !== null &&
                        ($maxClavien === null || $res->clavien_dindo_id > $maxClavien)
                    ) {
                        $maxClavien = $res->clavien_dindo_id;
                    }
                }
            }

            $internamento->update([
                'clavien_dindo_id' => $maxClavien,
            ]);
        });


        return redirect()->back()->with('success', 'Internamento atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        $path = $request->file('file')->store('imports');

        $service = new \App\Services\InternamentoImportService();
        $result = $service->import(storage_path("app/private/{$path}"));

        return back()->with([
            'imported' => $result['imported'],
            'importErrors' => $result['errors'],
        ]);
    }

    public function importBloco(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv'
        ]);

        $path = $request->file('file')->store('imports');

        $service = new BlocoOperatorioImportService();
        $result = $service->import(storage_path("app/private/{$path}"));

        return back()->with([
            'imported' => $result['imported'],
            'importErrors' => $result['errors'],
        ]);
    }
}
