<?php

namespace App\Http\Controllers;

use App\Models\Internamento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DistribuicaoController extends Controller
{
    public function index()
    {
        return Inertia::render('Internamento/Distribuicao', [
            'responsaveis' => User::where('ativo', 1)->get(['id', 'name']),
            'resultadoInicial' => [],
            'statsInicial' => [
                'semResponsavel' => 0,
                'comBloco' => 0,
                'semBloco' => 0,
            ],
            'logsInicial' => [],
        ]);
    }

    public function simular(Request $request)
    {
        $de = $request->data_entrada_de;
        $ate = $request->data_entrada_ate;

        // Responsáveis selecionados
        $responsaveis = User::whereIn('id', $request->responsaveis)
            ->where('ativo', 1)
            ->get();

        // Internamentos sem responsável no período
        $internamentos = Internamento::whereNull('responsavel_id')
            ->whereBetween('data_entrada', [$de, $ate])
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
    }

    public function executar(Request $request)
    {
        $de = $request->data_entrada_de;
        $ate = $request->data_entrada_ate;

        $responsaveis = User::whereIn('id', $request->responsaveis)
            ->where('ativo', 1)
            ->get();

        $internamentos = Internamento::whereNull('responsavel_id')
            ->whereBetween('data_entrada', [$de, $ate])
            ->get();

        $internamentosComBlocoIds = DB::table('bloco_operatorios')
            ->pluck('internamento_id')
            ->toArray();

        $comBloco = $internamentos->whereIn('id', $internamentosComBlocoIds)->values();
        $semBloco = $internamentos->whereNotIn('id', $internamentosComBlocoIds)->values();

        $distribuirExecucao = function ($grupo, $tipo) use ($responsaveis, $de, $ate, $internamentosComBlocoIds) {

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

            $pesos = [];
            foreach ($cargas as $id => $carga) {
                $pesos[$id] = 1 / ($carga + 1);
            }

            $totalPesos = array_sum($pesos);
            foreach ($pesos as $id => $peso) {
                $pesos[$id] = $peso / $totalPesos;
            }

            foreach ($grupo as $internamento) {

                $responsavelId = array_keys($pesos, max($pesos))[0];

                // GRAVAR NO BD
                $internamento->responsavel_id = $responsavelId;
                $internamento->save();

                $cargas[$responsavelId]++;
                $pesos[$responsavelId] = 1 / ($cargas[$responsavelId] + 1);

                $totalPesos = array_sum($pesos);
                foreach ($pesos as $id => $peso) {
                    $pesos[$id] = $peso / $totalPesos;
                }
            }
        };

        $distribuirExecucao($comBloco, 'com');
        $distribuirExecucao($semBloco, 'sem');

        return back()->with('success', 'Distribuição aplicada com sucesso.');
    }
}
