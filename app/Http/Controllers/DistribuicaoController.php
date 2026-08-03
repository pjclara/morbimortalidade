<?php

namespace App\Http\Controllers;

use App\Models\Internamento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class DistribuicaoController extends Controller
{
    public function index()
    {
        $responsaveis = User::query()
            ->where('ativo', true)
            ->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        $perfis = Role::orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Internamento/Distribuicao', [
            'responsaveis' => $responsaveis,
            'resultadoInicial' => [],
            'statsInicial' => [
                'semResponsavel' => 0,
                'comBloco' => 0,
                'semBloco' => 0,
            ],
            'logsInicial' => [],
            'perfis' => $perfis,
        ]);
    }

    public function simular(Request $request)
    {
        $validated = $request->validate([
            'data_entrada_de' => 'required|date',
            'data_entrada_ate' => 'required|date|after_or_equal:data_entrada_de',
            'responsaveis' => 'required|array',
            'responsaveis.*' => 'exists:users,id',
        ]);
        if ($validated) {


            $de = $request->data_entrada_de;
            $ate = $request->data_entrada_ate;

            $responsaveis = $request->responsaveis
                ? User::whereIn('id', $request->responsaveis)->where('ativo', 1)->get()
                : User::where('ativo', 1)->get();

            // Internamentos sem responsável no período
            $internamentos = Internamento::whereNull('responsavel_id')
                ->whereBetween('data_saida', [$de, $ate])
                ->get();

            // IDs dos internamentos com bloco operatório
            $internamentosComBlocoIds = DB::table('bloco_operatorios')
                ->pluck('internamento_id')
                ->toArray();

            // Separar internamentos
            $comBloco = $internamentos->whereIn('id', $internamentosComBlocoIds)->values();
            $semBloco = $internamentos->whereNotIn('id', $internamentosComBlocoIds)->values();

            // Estrutura inicial do resultado
            $resultado = [];
            foreach ($responsaveis as $r) {
                $resultado[$r->id] = [
                    'id' => $r->id,
                    'nome' => $r->name,
                    'com_bloco' => 0,
                    'sem_bloco' => 0,
                    'total' => 0,
                ];
            }

            // Função de distribuição proporcional
            $distribuir = function ($grupo, $tipo) use (&$resultado, $responsaveis, $de, $ate, $internamentosComBlocoIds) {

                // Carga atual por responsável
                $cargas = [];
                foreach ($responsaveis as $r) {

                    $query = Internamento::where('responsavel_id', $r->id)
                        ->whereBetween('data_entrada', [$de, $ate]);

                    if ($tipo === 'com') {
                        $query->whereIn('id', $internamentosComBlocoIds);
                    } else {
                        $query->whereNotIn('id', $internamentosComBlocoIds);
                    }

                    $cargas[$r->id] = $query->count();
                }

                // Pesos inversos
                $pesos = [];
                foreach ($cargas as $id => $carga) {
                    $pesos[$id] = 1 / ($carga + 1);
                }

                // Normalizar pesos
                $totalPesos = array_sum($pesos);
                foreach ($pesos as $id => $peso) {
                    $pesos[$id] = $peso / $totalPesos;
                }

                // Distribuir internamentos
                foreach ($grupo as $internamento) {

                    // Escolher responsável com maior peso
                    $responsavelId = array_keys($pesos, max($pesos))[0];

                    // Incrementar contadores
                    if ($tipo === 'com') {
                        $resultado[$responsavelId]['com_bloco']++;
                    } else {
                        $resultado[$responsavelId]['sem_bloco']++;
                    }

                    $resultado[$responsavelId]['total']++;

                    // Atualizar carga
                    $cargas[$responsavelId]++;

                    // Recalcular peso
                    $pesos[$responsavelId] = 1 / ($cargas[$responsavelId] + 1);

                    // Renormalizar
                    $totalPesos = array_sum($pesos);
                    foreach ($pesos as $id => $peso) {
                        $pesos[$id] = $peso / $totalPesos;
                    }
                }
            };

            // Distribuir COM bloco
            $distribuir($comBloco, 'com');

            // Distribuir SEM bloco
            $distribuir($semBloco, 'sem');

            return Inertia::render('Internamento/Distribuicao', [
                'responsaveis' => $responsaveis,
                'resultadoInicial' => array_values($resultado),
                'statsInicial' => [
                    'semResponsavel' => $internamentos->count(),
                    'comBloco' => $comBloco->count(),
                    'semBloco' => $semBloco->count(),
                ],
                'logsInicial' => [
                    [
                        'id' => 1,
                        'data' => now()->format('Y-m-d H:i'),
                        'mensagem' => 'Simulação concluída com sucesso.',
                    ]
                ],
            ]);
        } else {
            return back()->withErrors($validated);
        }
    }

    public function executar(Request $request)
    {
        $de = $request->data_entrada_de;
        $ate = $request->data_entrada_ate;

        DB::transaction(function () use ($request, $de, $ate) {

            $responsaveis = User::whereIn('id', $request->responsaveis)
                ->where('ativo', 1)
                ->get();

            if ($responsaveis->isEmpty()) {
                return;
            }

            $internamentos = Internamento::whereNull('responsavel_id')
                ->whereBetween('data_saida', [$de, $ate])
                ->get();

            $internamentosComBlocoIds = DB::table('bloco_operatorios')
                ->pluck('internamento_id');

            $comBloco = $internamentos
                ->whereIn('id', $internamentosComBlocoIds)
                ->values();

            $semBloco = $internamentos
                ->whereNotIn('id', $internamentosComBlocoIds)
                ->values();

            // Cargas atuais COM bloco
            $cargasCom = Internamento::select(
                'responsavel_id',
                DB::raw('COUNT(*) as total')
            )
                ->whereNotNull('responsavel_id')
                ->whereBetween('data_entrada', [$de, $ate])
                ->whereIn('id', $internamentosComBlocoIds)
                ->groupBy('responsavel_id')
                ->pluck('total', 'responsavel_id')
                ->toArray();

            // Cargas atuais SEM bloco
            $cargasSem = Internamento::select(
                'responsavel_id',
                DB::raw('COUNT(*) as total')
            )
                ->whereNotNull('responsavel_id')
                ->whereBetween('data_entrada', [$de, $ate])
                ->whereNotIn('id', $internamentosComBlocoIds)
                ->groupBy('responsavel_id')
                ->pluck('total', 'responsavel_id')
                ->toArray();

            $distribuir = function ($grupo, $tipo, &$cargasBase) use ($responsaveis) {

                $cargas = [];

                foreach ($responsaveis as $r) {
                    $cargas[$r->id] = $cargasBase[$r->id] ?? 0;
                }

                foreach ($grupo as $internamento) {

                    // Calcular pesos
                    $pesos = [];

                    foreach ($cargas as $id => $carga) {
                        $pesos[$id] = 1 / ($carga + 1);
                    }

                    $maiorPeso = max($pesos);

                    // Responsáveis empatados
                    $candidatos = [];

                    foreach ($pesos as $id => $peso) {
                        if ($peso == $maiorPeso) {
                            $candidatos[] = $id;
                        }
                    }

                    // Escolha aleatória em caso de empate
                    $responsavelId = $candidatos[array_rand($candidatos)];

                    $internamento->update([
                        'responsavel_id' => $responsavelId,
                    ]);

                    $cargas[$responsavelId]++;
                }
            };

            $distribuir($comBloco, 'com', $cargasCom);
            $distribuir($semBloco, 'sem', $cargasSem);
        });

        return back()->with('success', 'Distribuição aplicada com sucesso.');
    }
}
