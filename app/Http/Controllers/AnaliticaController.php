<?php

namespace App\Http\Controllers;

use App\Models\BlocoOperatorio;
use App\Models\Internamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnaliticaController extends Controller
{
    public function index(Request $request)
    {
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        $equipaId = $request->query('equipa_id');

        // ---------------------------------------------------------------
        // Queries base, com os filtros aplicados. São clonadas em cada
        // agregação para não interferirem entre si.
        // ---------------------------------------------------------------
        $internamentoQuery = Internamento::query();
        $blocoQuery = BlocoOperatorio::query();

        if ($dataInicio) {
            $internamentoQuery->where('data_entrada', '>=', Carbon::parse($dataInicio)->startOfDay());
            $blocoQuery->where('data_intervencao', '>=', Carbon::parse($dataInicio)->startOfDay());
        }

        if ($dataFim) {
            $internamentoQuery->where('data_entrada', '<=', Carbon::parse($dataFim)->endOfDay());
            $blocoQuery->where('data_intervencao', '<=', Carbon::parse($dataFim)->endOfDay());
        }

        if ($equipaId) {
            $internamentoQuery->where('equipa_id', $equipaId);
        }

        $internamentoIds = (clone $internamentoQuery)->pluck('id');
        $totalInternamentos = $internamentoIds->count();
        $totalCirurgias = (clone $blocoQuery)->count();

        // ---------------------------------------------------------------
        // KPIs globais
        // ---------------------------------------------------------------
        $falecidos = (clone $internamentoQuery)->where('falecido', 1)->count();
        $falecidosAposAlta = (clone $internamentoQuery)->where('falecido_apos_alta', 1)->count();
        $ambulatorio = (clone $blocoQuery)->where('ambulatorio', 'S')->count();
        $comComplicacoes = $totalInternamentos
            ? DB::table('complicacao_internamento')
                ->whereIn('internamento_id', $internamentoIds)
                ->distinct('internamento_id')
                ->count('internamento_id')
            : 0;

        $kpis = [
            'totalInternamentos' => $totalInternamentos,
            'totalCirurgias' => $totalCirurgias,
            'mediaDiasInternamento' => round((clone $internamentoQuery)->avg('dias_internamento') ?? 0, 1),
            'taxaMortalidade' => $totalInternamentos ? round($falecidos / $totalInternamentos * 100, 1) : 0,
            'taxaMortalidadeAposAlta' => $totalInternamentos ? round($falecidosAposAlta / $totalInternamentos * 100, 1) : 0,
            'taxaAmbulatorio' => $totalCirurgias ? round($ambulatorio / $totalCirurgias * 100, 1) : 0,
            'taxaComplicacoes' => $totalInternamentos ? round($comComplicacoes / $totalInternamentos * 100, 1) : 0,
        ];

        // ---------------------------------------------------------------
        // Séries temporais (por mês)
        // ---------------------------------------------------------------
        $internamentosPorMes = (clone $internamentoQuery)
            ->selectRaw("DATE_FORMAT(data_entrada, '%Y-%m') as mes, COUNT(*) as total, AVG(dias_internamento) as media_dias")
            ->groupByRaw("DATE_FORMAT(data_entrada, '%Y-%m')")
            ->orderBy('mes')
            ->get()
            ->map(fn($r) => [
                'mes' => $r->mes,
                'total' => (int) $r->total,
                'media_dias' => round((float) $r->media_dias, 1),
            ]);

        $cirurgiasPorMes = (clone $blocoQuery)
            ->selectRaw("
                DATE_FORMAT(data_intervencao, '%Y-%m') as mes,
                COUNT(*) as total,
                SUM(CASE WHEN ambulatorio = 'S' THEN 1 ELSE 0 END) as ambulatorio,
                SUM(CASE WHEN ambulatorio = 'N' THEN 1 ELSE 0 END) as internamento
            ")
            ->groupByRaw("DATE_FORMAT(data_intervencao, '%Y-%m')")
            ->orderBy('mes')
            ->get()
            ->map(fn($r) => [
                'mes' => $r->mes,
                'total' => (int) $r->total,
                'ambulatorio' => (int) $r->ambulatorio,
                'internamento' => (int) $r->internamento,
            ]);

        // ---------------------------------------------------------------
        // Distribuições — Internamentos
        // ---------------------------------------------------------------
        $porEquipa = DB::table('internamentos')
            ->join('equipas', 'equipas.id', '=', 'internamentos.equipa_id')
            ->whereIn('internamentos.id', $internamentoIds)
            ->selectRaw("
                equipas.id, equipas.nome,
                COUNT(*) as total,
                AVG(internamentos.dias_internamento) as media_dias,
                SUM(CASE WHEN internamentos.falecido = 1 THEN 1 ELSE 0 END) as obitos
            ")
            ->groupBy('equipas.id', 'equipas.nome')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => [
                'nome' => $r->nome,
                'total' => (int) $r->total,
                'media_dias' => round((float) $r->media_dias, 1),
                'obitos' => (int) $r->obitos,
            ]);

        $porDestino = DB::table('internamentos')
            ->join('destinos', 'destinos.id', '=', 'internamentos.destino_id')
            ->whereIn('internamentos.id', $internamentoIds)
            ->selectRaw('destinos.nome, COUNT(*) as total')
            ->groupBy('destinos.id', 'destinos.nome')
            ->orderByDesc('total')
            ->get();

        $porOrigem = DB::table('internamentos')
            ->join('origems', 'origems.id', '=', 'internamentos.origem_id')
            ->whereIn('internamentos.id', $internamentoIds)
            ->selectRaw('origems.nome, COUNT(*) as total')
            ->groupBy('origems.id', 'origems.nome')
            ->orderByDesc('total')
            ->get();

        $porClavienDindo = DB::table('internamentos')
            ->join('clavien_dindos', 'clavien_dindos.id', '=', 'internamentos.clavien_dindo_id')
            ->whereIn('internamentos.id', $internamentoIds)
            ->selectRaw('clavien_dindos.nome, COUNT(*) as total')
            ->groupBy('clavien_dindos.id', 'clavien_dindos.nome')
            ->orderByDesc('total')
            ->get();

        $porSexo = DB::table('internamentos')
            ->join('patients', 'patients.id', '=', 'internamentos.patient_id')
            ->join('sexos', 'sexos.id', '=', 'patients.sexo_id')
            ->whereIn('internamentos.id', $internamentoIds)
            ->selectRaw('sexos.nome, COUNT(*) as total')
            ->groupBy('sexos.id', 'sexos.nome')
            ->get();

        $porFaixaEtaria = DB::table('internamentos')
            ->join('patients', 'patients.id', '=', 'internamentos.patient_id')
            ->whereIn('internamentos.id', $internamentoIds)
            ->whereNotNull('patients.data_nascimento')
            ->selectRaw("TIMESTAMPDIFF(YEAR, patients.data_nascimento, internamentos.data_entrada) as idade")
            ->get()
            ->groupBy(function ($row) {
                $idade = (int) $row->idade;
                return match (true) {
                    $idade < 18 => '0-17',
                    $idade < 40 => '18-39',
                    $idade < 60 => '40-59',
                    $idade < 75 => '60-74',
                    default => '75+',
                };
            })
            ->map(fn($grupo, $faixa) => ['faixa' => $faixa, 'total' => $grupo->count()])
            ->values()
            ->sortBy(fn($f) => array_search($f['faixa'], ['0-17', '18-39', '40-59', '60-74', '75+']))
            ->values();

        $topDiagnosticos = DB::table('diagnostico_internamento')
            ->join('diagnosticos', 'diagnosticos.id', '=', 'diagnostico_internamento.diagnostico_id')
            ->whereIn('diagnostico_internamento.internamento_id', $internamentoIds)
            ->where('principal', true)
            ->selectRaw('diagnosticos.nome, COUNT(*) as total')
            ->groupBy('diagnosticos.id', 'diagnosticos.nome')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $topComplicacoes = DB::table('complicacao_internamento')
            ->join('complicacaos', 'complicacaos.id', '=', 'complicacao_internamento.complicacao_id')
            ->join('grupo_complicacaos', 'grupo_complicacaos.id', '=', 'complicacaos.grupo_complicacao_id')
            ->whereIn('complicacao_internamento.internamento_id', $internamentoIds)
            ->selectRaw('grupo_complicacaos.nome as grupo, COUNT(*) as total')
            ->groupBy('grupo_complicacaos.id', 'grupo_complicacaos.nome')
            ->orderByDesc('total')
            ->get();

        // ---------------------------------------------------------------
        // Distribuições — Bloco Operatório
        // ---------------------------------------------------------------
        $blocoIds = (clone $blocoQuery)->pluck('id');

        $porTipoCirurgia = DB::table('bloco_operatorios')
            ->join('tipo_de_cirurgias', 'tipo_de_cirurgias.id', '=', 'bloco_operatorios.tipo_de_cirurgia_id')
            ->whereIn('bloco_operatorios.id', $blocoIds)
            ->selectRaw('tipo_de_cirurgias.nome, COUNT(*) as total')
            ->groupBy('tipo_de_cirurgias.id', 'tipo_de_cirurgias.nome')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $diasSemana = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        $porDiaSemana = (clone $blocoQuery)
            ->selectRaw('DAYOFWEEK(data_intervencao) as dia, COUNT(*) as total')
            ->groupByRaw('DAYOFWEEK(data_intervencao)')
            ->get()
            ->map(fn($r) => ['dia' => $diasSemana[$r->dia - 1], 'total' => (int) $r->total])
            // Segunda a Domingo, ordem de trabalho da semana cirúrgica.
            ->sortBy(fn($r) => (array_search($r['dia'], $diasSemana) + 6) % 7)
            ->values();

        $topProcedimentos = DB::table('bloco_operatorio_procedimento')
            ->join('procedimentos', 'procedimentos.id', '=', 'bloco_operatorio_procedimento.procedimento_id')
            ->whereIn('bloco_operatorio_procedimento.bloco_operatorio_id', $blocoIds)
            ->selectRaw('procedimentos.nome, COUNT(*) as total')
            ->groupBy('procedimentos.id', 'procedimentos.nome')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return Inertia::render('Analitica/Index', [
            'kpis' => $kpis,
            'internamentosPorMes' => $internamentosPorMes,
            'cirurgiasPorMes' => $cirurgiasPorMes,
            'porEquipa' => $porEquipa,
            'porDestino' => $porDestino,
            'porOrigem' => $porOrigem,
            'porClavienDindo' => $porClavienDindo,
            'porSexo' => $porSexo,
            'porFaixaEtaria' => $porFaixaEtaria,
            'topDiagnosticos' => $topDiagnosticos,
            'topComplicacoes' => $topComplicacoes,
            'porTipoCirurgia' => $porTipoCirurgia,
            'porDiaSemana' => $porDiaSemana,
            'topProcedimentos' => $topProcedimentos,
            'equipas' => DB::table('equipas')->select('id', 'nome')->orderBy('nome')->get(),
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'equipa_id' => $equipaId,
            ],
        ]);
    }
}
