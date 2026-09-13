<?php

namespace App\Http\Controllers;

use App\Models\BlocoOperatorio;
use App\Models\Icd10CapitulosSecoes;
use App\Models\Icd10CM;
use App\Models\Icd10PcsAxis2Option;
use App\Models\Icd10PcsAxis4Option;
use App\Models\Internamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnaliticaController extends Controller
{
    // Sem filtro de data explícito, mostra só os últimos 3 meses — a página
    // ficava lenta a agregar anos de internamentos/cirurgias de uma vez.
    private const PERIODO_OMISSAO_MESES = 3;

    public function index(Request $request)
    {
        $dataInicio = $request->query('data_inicio');
        $dataFim = $request->query('data_fim');
        $equipaId = $request->query('equipa_id');
        $verTudo = $request->boolean('todos');

        // Sem datas nem pedido explícito de "ver tudo", aplica o período por
        // omissão — evita agregar anos de histórico em cada carregamento.
        $periodoPorOmissao = ! $dataInicio && ! $dataFim && ! $verTudo;
        if ($periodoPorOmissao) {
            $dataFim = Carbon::now()->toDateString();
            $dataInicio = Carbon::now()->subMonths(self::PERIODO_OMISSAO_MESES)->toDateString();
        }

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

        // Contagem por código de diagnóstico principal — join simples e indexado
        // (diagnostico_internamento.diagnostico_id / internamento_id), sem
        // qualquer cruzamento com os catálogos CID-10.
        $diagnosticoCounts = DB::table('diagnostico_internamento')
            ->join('diagnosticos', 'diagnosticos.id', '=', 'diagnostico_internamento.diagnostico_id')
            ->whereIn('diagnostico_internamento.internamento_id', $internamentoIds)
            ->where('principal', true)
            ->selectRaw('diagnosticos.codigo, diagnosticos.nome, COUNT(*) as total')
            ->groupBy('diagnosticos.codigo', 'diagnosticos.nome')
            ->orderByDesc('total')
            ->get();

        // Descrições oficiais CID-10-CM (Icd10CM) para os códigos mais frequentes —
        // troca o nome abreviado do catálogo interno pela descrição completa
        // quando existe correspondência exata (código é chave única e indexada).
        $codigosTop10 = $diagnosticoCounts->take(10)->pluck('codigo');
        $descricoesIcd10Cm = Icd10CM::query()
            ->whereIn('code', $codigosTop10)
            ->pluck('description', 'code');

        $topDiagnosticos = $diagnosticoCounts->take(10)->map(fn($r) => [
            'nome' => $descricoesIcd10Cm[$r->codigo] ?? $r->nome,
            'total' => (int) $r->total,
        ])->values();

        // Classificação por capítulo/secção CID-10 — feita em memória a partir do
        // model Icd10CapitulosSecoes (só 235 linhas, cacheadas) em vez de um JOIN
        // SQL por intervalo de código: essa comparação exigia converter a
        // collation em cada linha e corria sem índice, e era o principal motivo
        // de lentidão da página.
        $faixasIcd10 = Cache::rememberForever('icd10_capitulos_secoes.faixas', function () {
            return Icd10CapitulosSecoes::query()
                ->orderBy('codigo_inicio')
                ->get(['codigo_inicio', 'codigo_fim', 'capitulo_descricao', 'seccao_descricao'])
                ->all();
        });

        $porCapitulo = [];
        $porSeccao = [];

        foreach ($diagnosticoCounts as $r) {
            $prefixo = substr($r->codigo, 0, 3);

            foreach ($faixasIcd10 as $faixa) {
                if ($prefixo < $faixa->codigo_inicio) {
                    break; // faixas ordenadas por codigo_inicio e não sobrepostas
                }
                if ($prefixo <= $faixa->codigo_fim) {
                    $porCapitulo[$faixa->capitulo_descricao] = ($porCapitulo[$faixa->capitulo_descricao] ?? 0) + $r->total;
                    $porSeccao[$faixa->seccao_descricao] = ($porSeccao[$faixa->seccao_descricao] ?? 0) + $r->total;
                    break;
                }
            }
        }

        arsort($porCapitulo);
        arsort($porSeccao);

        $porCapituloDiagnostico = collect($porCapitulo)
            ->map(fn($total, $nome) => ['nome' => $nome, 'total' => (int) $total])
            ->values();

        $porSeccaoDiagnostico = collect($porSeccao)
            ->take(10)
            ->map(fn($total, $nome) => ['nome' => $nome, 'total' => (int) $total])
            ->values();

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

        $procedimentoCounts = DB::table('bloco_operatorio_procedimento')
            ->join('procedimentos', 'procedimentos.id', '=', 'bloco_operatorio_procedimento.procedimento_id')
            ->whereIn('bloco_operatorio_procedimento.bloco_operatorio_id', $blocoIds)
            ->selectRaw('procedimentos.codigo, procedimentos.nome, COUNT(*) as total')
            ->groupBy('procedimentos.id', 'procedimentos.codigo', 'procedimentos.nome')
            ->orderByDesc('total')
            ->get();

        $topProcedimentos = $procedimentoCounts->take(10)->map(fn($r) => [
            'nome' => $r->nome,
            'total' => (int) $r->total,
        ])->values();

        // Sistema corporal (ICD-10-PCS, eixo 2) — os 2 primeiros caracteres do
        // código do procedimento identificam secção+sistema corporal; junção
        // exata (sem intervalos), por isso um simples lookup em memória a
        // partir do model Icd10PcsAxis2Option (só 116 linhas, cacheadas).
        $sistemasCorporais = Cache::rememberForever('icd10_pcs_axis_2_options.prefixos', function () {
            return Icd10PcsAxis2Option::query()
                ->pluck('description', 'prefix');
        });

        $porSistemaCorporal = [];
        foreach ($procedimentoCounts as $r) {
            $prefixo = substr($r->codigo, 0, 2);
            $nome = $sistemasCorporais[$prefixo] ?? 'Não classificado';
            $porSistemaCorporal[$nome] = ($porSistemaCorporal[$nome] ?? 0) + $r->total;
        }
        arsort($porSistemaCorporal);

        $porSistemaCorporal = collect($porSistemaCorporal)
            ->map(fn($total, $nome) => ['nome' => $nome, 'total' => (int) $total])
            ->values();

        // Parte do corpo (ICD-10-PCS, eixo 4) — os 4 primeiros caracteres do
        // código. Ao contrário do eixo 2, esta tabela tem 12 mil linhas, pelo
        // que não vale a pena cachear por inteiro: procura-se só pelos
        // prefixos realmente usados nos procedimentos deste período
        // (correspondência exata, coluna "prefix" é chave única e indexada).
        $prefixosParteCorpo = $procedimentoCounts->map(fn($r) => substr($r->codigo, 0, 4))->unique()->values();
        $partesCorpo = Icd10PcsAxis4Option::query()
            ->whereIn('prefix', $prefixosParteCorpo)
            ->pluck('description', 'prefix');

        $porParteCorpo = [];
        foreach ($procedimentoCounts as $r) {
            $prefixo = substr($r->codigo, 0, 4);
            $nome = $partesCorpo[$prefixo] ?? 'Não classificado';
            $porParteCorpo[$nome] = ($porParteCorpo[$nome] ?? 0) + $r->total;
        }
        arsort($porParteCorpo);

        $porParteCorpo = collect($porParteCorpo)
            ->take(10)
            ->map(fn($total, $nome) => ['nome' => $nome, 'total' => (int) $total])
            ->values();

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
            'porCapituloDiagnostico' => $porCapituloDiagnostico,
            'porSeccaoDiagnostico' => $porSeccaoDiagnostico,
            'topComplicacoes' => $topComplicacoes,
            'porTipoCirurgia' => $porTipoCirurgia,
            'porDiaSemana' => $porDiaSemana,
            'topProcedimentos' => $topProcedimentos,
            'porSistemaCorporal' => $porSistemaCorporal,
            'porParteCorpo' => $porParteCorpo,
            'equipas' => DB::table('equipas')->select('id', 'nome')->orderBy('nome')->get(),
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'equipa_id' => $equipaId,
            ],
            'periodoPorOmissao' => $periodoPorOmissao,
        ]);
    }
}
