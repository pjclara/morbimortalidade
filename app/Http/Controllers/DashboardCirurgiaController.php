<?php

namespace App\Http\Controllers;

use App\Models\BlocoOperatorio;
use App\Models\Internamento;
use App\Models\Procedimento;
use App\Models\TipoDeCirurgia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardCirurgiaController extends Controller
{
    public function index(Request $request)
    {
        $blocoQuery = BlocoOperatorio::with('tipoDeCirurgia', 'internamento');
        $internamentoQuery = Internamento::with('patient', 'equipa');

        // Filtros
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        $tipoFiltro = $request->query('tipo_filtro'); // 'ambulatorio', 'internamento', or null for all
        $tipoCirurgia = $request->query('tipo_cirurgia');
        $bloco = $request->query('bloco');

        // Aplicar filtros à query de cirurgias
        if ($dataInicio) {
            $blocoQuery->where('data_intervencao', '>=', Carbon::parse($dataInicio)->startOfDay());
            $internamentoQuery->where('data_entrada', '>=', Carbon::parse($dataInicio)->startOfDay());
        }

        if ($dataFim) {
            $blocoQuery->where('data_intervencao', '<=', Carbon::parse($dataFim)->endOfDay());
            $internamentoQuery->where('data_entrada', '<=', Carbon::parse($dataFim)->endOfDay());
        }

        if ($tipoFiltro === 'ambulatorio') {
            $blocoQuery->where('ambulatorio', 'S');
        } elseif ($tipoFiltro === 'internamento') {
            $blocoQuery->where('ambulatorio', 'N');
        }

        if ($tipoCirurgia) {
            $blocoQuery->where('tipo_de_cirurgia_id', $tipoCirurgia);
        }

        if ($bloco) {
            $blocoQuery->where('bloco_num', $bloco);
        }

        // Estatísticas globais
        $globalStats = BlocoOperatorio::selectRaw('
            COUNT(*) as totalCirurgias,
            SUM(CASE WHEN ambulatorio = "S" THEN 1 ELSE 0 END) as totalAmbulatorio,
            SUM(CASE WHEN ambulatorio = "N" THEN 1 ELSE 0 END) as totalInternamento
        ')->first();

        $totalStats = [
            'totalCirurgias' => $globalStats->totalCirurgias ?? 0,
            'totalAmbulatorio' => $globalStats->totalAmbulatorio ?? 0,
            'totalInternamento' => $globalStats->totalInternamento ?? 0,
            'totalInternamentos' => \count(Internamento::query()->get() ?? []),
            'totalProcedimentos' => \count(Procedimento::query()->get() ?? []),
        ];

        // Estatísticas filtradas
        $filteredQuery = (clone $blocoQuery)->selectRaw('
            COUNT(*) as totalCirurgias,
            SUM(CASE WHEN ambulatorio = "S" THEN 1 ELSE 0 END) as totalAmbulatorio,
            SUM(CASE WHEN ambulatorio = "N" THEN 1 ELSE 0 END) as totalInternamento
        ')->first();

        $filteredStats = [
            'totalCirurgias' => $filteredQuery->totalCirurgias ?? 0,
            'totalAmbulatorio' => $filteredQuery->totalAmbulatorio ?? 0,
            'totalInternamento' => $filteredQuery->totalInternamento ?? 0,
            'totalInternamentos' => $internamentoQuery->count(),
        ];

        $topTiposQuery = TipoDeCirurgia::query()
            ->selectRaw('tipo_de_cirurgias.id, tipo_de_cirurgias.nome, COUNT(bloco_operatorios.id) as total')
            ->leftJoin('bloco_operatorios', 'tipo_de_cirurgias.id', '=', 'bloco_operatorios.tipo_de_cirurgia_id');

        // FILTRO POR DATA
        if ($dataInicio) {
            $topTiposQuery->where('bloco_operatorios.data_intervencao', '>=', Carbon::parse($dataInicio)->startOfDay());
        }

        if ($dataFim) {
            $topTiposQuery->where('bloco_operatorios.data_intervencao', '<=', Carbon::parse($dataFim)->endOfDay());
        }

        return Inertia::render('dashboard', [
            'stats' => $filteredStats,
            'totalStats' => $totalStats,
            'topTipos' => $topTiposQuery
                ->groupBy('tipo_de_cirurgias.id', 'tipo_de_cirurgias.nome')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),

            'cirurgiasPorMes' => (clone $blocoQuery)->selectRaw("
                    DATE_FORMAT(data_intervencao, '%Y-%m') as mes,
                    COUNT(*) as total,
                    MIN(data_intervencao) as min_data
                ")
                ->groupByRaw("DATE_FORMAT(data_intervencao, '%Y-%m')")
                ->orderByRaw("MIN(data_intervencao) asc")
                ->get(),

            'ultimas' => (clone $blocoQuery)->with('tipoDeCirurgia', 'internamento')
                ->orderByDesc('data_intervencao')
                ->limit(10)
                ->get(),


            'tiposCirurgia' => TipoDeCirurgia::query()->select('id', 'nome')->get(),

            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'tipo_filtro' => $tipoFiltro,
                'tipo_cirurgia' => $tipoCirurgia,
                'bloco' => $bloco,
            ],
        ]);
    }
}
