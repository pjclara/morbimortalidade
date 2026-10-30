<?php

namespace App\Http\Controllers;

use App\Models\Internamento;
use App\Models\Lotacao;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Reproduz, com dados reais da base de dados, a estrutura do relatório
 * "Internamento de Cirurgia: movimento assistencial" (HSA/ULSRL): os números
 * não coincidem com os do PDF original (são de doentes e período diferentes),
 * mas os indicadores e a forma de os calcular são os mesmos.
 */
class RelatorioCirurgiaController extends Controller
{
    private const FAIXAS_IDADE = ['18-44', '45-64', '65-74', '75-84', '85+'];

    public function index(Request $request)
    {
        return Inertia::render('Relatorio/Cirurgia', $this->construirDados($request));
    }

    public function pdf(Request $request)
    {
        $dados = $this->construirDados($request);

        $pdf = Pdf::loadView('relatorios.cirurgia-pdf', $dados)
            ->setPaper('a4', 'portrait');

        return $pdf->stream("movimento-assistencial-{$dados['periodo']['data_inicio']}_a_{$dados['periodo']['data_fim']}.pdf");
    }

    private function construirDados(Request $request): array
    {
        $primeiraLotacao = Lotacao::orderBy('data_inicio')->first();
        $ultimaLotacao = Lotacao::orderByDesc('data_fim')->first();

        $dataInicio = Carbon::parse($request->query(
            'data_inicio',
            $primeiraLotacao?->data_inicio?->toDateString() ?? Internamento::min('data_entrada') ?? Carbon::now()->subYear()->toDateString()
        ))->startOfDay();

        $dataFim = Carbon::parse($request->query(
            'data_fim',
            $ultimaLotacao?->data_fim?->toDateString() ?? Carbon::now()->toDateString()
        ))->endOfDay();

        $lotacoes = Lotacao::orderBy('data_inicio')->get();

        // Episódios "saídos" no período — mesmo critério usado no resto da
        // aplicação (Analitica): filtra por data_saida.
        $episodios = Internamento::query()
            ->whereBetween('data_saida', [$dataInicio, $dataFim])
            ->with(['origem', 'destino', 'patient'])
            ->get();

        $totalEpisodios = $episodios->count();

        // -----------------------------------------------------------------
        // Lotação / ocupação — dias-cama disponíveis e dias-doente, com
        // overlap dos episódios (incluindo os ainda sem data_saida,
        // censurados no fim do período) contra o período pedido.
        // -----------------------------------------------------------------
        $episodiosAbertos = Internamento::query()
            ->where('data_entrada', '<=', $dataFim)
            ->where(function ($q) use ($dataInicio) {
                $q->whereNull('data_saida')->orWhere('data_saida', '>=', $dataInicio);
            })
            ->get(['id', 'data_entrada', 'data_saida', 'patient_id']);

        $diasDoentePeriodo = $this->diasDoenteEntre($episodiosAbertos, $dataInicio, $dataFim);

        // Censo dia a dia (quantos doentes internados em cada dia) vs. lotação
        // desse dia — usado para identificar dias em que o serviço esteve
        // acima da lotação (doentes "sem cama própria": em maca extra, no
        // corredor, etc., consoante a organização do serviço).
        $censoDiario = $this->censoDiario($episodiosAbertos, $lotacoes, $dataInicio, $dataFim);

        $diasCamaPeriodo = 0.0;
        foreach ($lotacoes as $lot) {
            $fim = $lot->data_fim ? Carbon::instance($lot->data_fim)->endOfDay() : $dataFim;
            $inicio = Carbon::instance($lot->data_inicio)->startOfDay();
            $overlapInicio = $inicio->greaterThan($dataInicio) ? $inicio : $dataInicio;
            $overlapFim = $fim->lessThan($dataFim) ? $fim : $dataFim;
            if ($overlapFim->greaterThanOrEqualTo($overlapInicio)) {
                $diasCamaPeriodo += ($overlapInicio->diffInDays($overlapFim) + 1) * $lot->numero_camas;
            }
        }

        $diasNoPeriodo = max(1, $dataInicio->diffInDays($dataFim) + 1);
        $camasMediaPeriodo = $diasCamaPeriodo > 0 ? $diasCamaPeriodo / $diasNoPeriodo : 0;
        $taxaOcupacao = $diasCamaPeriodo > 0 ? round($diasDoentePeriodo / $diasCamaPeriodo * 100, 1) : 0;

        $doentesDistintos = $episodios->pluck('patient_id')->unique()->count();
        $doentesPorCama = $camasMediaPeriodo > 0 ? round($doentesDistintos / $camasMediaPeriodo, 0) : 0;
        $diaCamaVazia = $totalEpisodios > 0 ? round(max(0, $diasCamaPeriodo - $diasDoentePeriodo) / $totalEpisodios, 1) : 0;

        // -----------------------------------------------------------------
        // Mortalidade, altas que saem depois da alta clínica
        // -----------------------------------------------------------------
        $falecidos = $episodios->where('falecido', true)->count();
        $saiDepoisAlta = $episodios->filter(fn($e) => $this->saiDepoisDaAltaClinica($e))->count();

        $diasInternamentoOrdenados = $episodios->pluck('dias_internamento')->filter(fn($d) => $d !== null)->sort()->values();
        $pctAteTresDias = $diasInternamentoOrdenados->count()
            ? round($diasInternamentoOrdenados->filter(fn($d) => $d <= 3)->count() / $diasInternamentoOrdenados->count() * 100, 1)
            : 0;

        // -----------------------------------------------------------------
        // Readmissões a 30 dias — para os doentes com episódios no período,
        // usa o histórico completo (mesmo fora do período) para detectar o
        // episódio anterior.
        // -----------------------------------------------------------------
        $readmissoes = $this->calcularReadmissoes($episodios, $dataInicio, $dataFim);

        // -----------------------------------------------------------------
        // Doentes à espera de cama — dias em que o censo diário excede a
        // lotação registada para esse dia.
        // -----------------------------------------------------------------
        $excessos = array_map(fn($d) => max(0, $d['censo'] - $d['lotacao']), $censoDiario);
        $diasAcimaLotacao = count(array_filter($excessos, fn($v) => $v > 0));
        $excessosPositivos = array_filter($excessos, fn($v) => $v > 0);

        $esperaCama = [
            'diasAcimaLotacao' => $diasAcimaLotacao,
            'pctDiasAcimaLotacao' => count($censoDiario) ? round($diasAcimaLotacao / count($censoDiario) * 100, 1) : 0,
            'picoDoentesSemCama' => $excessos ? max($excessos) : 0,
            'mediaDoentesSemCamaNosDiasAcima' => $excessosPositivos ? round(array_sum($excessosPositivos) / count($excessosPositivos), 1) : 0,
        ];

        $anoNumeros = [
            'episodios' => $totalEpisodios,
            'demoraMedia' => round($episodios->avg('dias_internamento') ?? 0, 1),
            'pctAteTresDias' => $pctAteTresDias,
            'taxaOcupacao' => $taxaOcupacao,
            'mortalidade' => $totalEpisodios ? round($falecidos / $totalEpisodios * 100, 1) : 0,
            'doentesPorCama' => $doentesPorCama,
            'doentesDistintos' => $doentesDistintos,
            'diaCamaVazia' => $diaCamaVazia,
            'readmissoes30dias' => $readmissoes['pct30'],
            'readmissoesPelaUrgencia' => $readmissoes['pctUrgencia'],
            'altasDepoisDaAltaClinica' => $totalEpisodios ? round($saiDepoisAlta / $totalEpisodios * 100, 1) : 0,
            'diasAcimaLotacao' => $esperaCama['diasAcimaLotacao'],
            'pctDiasAcimaLotacao' => $esperaCama['pctDiasAcimaLotacao'],
            'picoDoentesSemCama' => $esperaCama['picoDoentesSemCama'],
            'mediaDoentesSemCamaNosDiasAcima' => $esperaCama['mediaDoentesSemCamaNosDiasAcima'],
        ];

        // -----------------------------------------------------------------
        // Série mensal — doentes internados (média diária) vs. lotação
        // -----------------------------------------------------------------
        $porMes = [];
        $cursor = $dataInicio->copy()->startOfMonth();
        $fimMeses = $dataFim->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($fimMeses)) {
            $inicioMes = $cursor->copy()->startOfMonth()->max($dataInicio);
            $fimMes = $cursor->copy()->endOfMonth()->min($dataFim);

            $diasDoenteMes = $this->diasDoenteEntre($episodiosAbertos, $inicioMes, $fimMes);
            $diasMes = $inicioMes->diffInDays($fimMes) + 1;

            $lotacaoMeioMes = $lotacoes->first(function ($lot) use ($cursor) {
                $meio = $cursor->copy()->addDays(14);
                $fim = $lot->data_fim ?? Carbon::now();
                return $meio->greaterThanOrEqualTo($lot->data_inicio) && $meio->lessThanOrEqualTo($fim);
            });

            $porMes[] = [
                'mes' => $cursor->format('Y-m'),
                'doentesMedia' => $diasMes > 0 ? round($diasDoenteMes / $diasMes, 1) : 0,
                'lotacao' => $lotacaoMeioMes?->numero_camas,
            ];

            $cursor->addMonth();
        }

        // -----------------------------------------------------------------
        // Tabela por período de lotação
        // -----------------------------------------------------------------
        $porPeriodoLotacao = $lotacoes->map(function ($lot) use ($episodios, $dataInicio, $dataFim, $episodiosAbertos, $censoDiario) {
            $fim = $lot->data_fim ? Carbon::instance($lot->data_fim)->endOfDay() : $dataFim;
            $inicio = Carbon::instance($lot->data_inicio)->startOfDay();
            $inicioEfetivo = $inicio->greaterThan($dataInicio) ? $inicio : $dataInicio;
            $fimEfetivo = $fim->lessThan($dataFim) ? $fim : $dataFim;

            if ($fimEfetivo->lessThan($inicioEfetivo)) {
                return null;
            }

            $dias = $inicioEfetivo->diffInDays($fimEfetivo) + 1;
            $diasDoente = $this->diasDoenteEntre($episodiosAbertos, $inicioEfetivo, $fimEfetivo);
            $episodiosPeriodo = $episodios->filter(
                fn($e) => $e->data_saida && Carbon::parse($e->data_saida)->between($inicioEfetivo, $fimEfetivo)
            );
            $saidas = $episodiosPeriodo->count();
            $obitos = $episodiosPeriodo->where('falecido', true)->count();
            $diasCama = $dias * $lot->numero_camas;

            $diasAcimaLotacaoPeriodo = collect($censoDiario)
                ->filter(fn($d) => $d['data'] >= $inicioEfetivo->toDateString() && $d['data'] <= $fimEfetivo->toDateString())
                ->filter(fn($d) => $d['censo'] > $d['lotacao'])
                ->count();

            return [
                'periodo' => $lot->observacoes ?: ($inicio->format('d/m/Y') . ' – ' . $fim->format('d/m/Y')),
                'lotacao' => $lot->numero_camas,
                'doentesMedia' => round($diasDoente / max(1, $dias), 1),
                'ocupacao' => $diasCama > 0 ? round($diasDoente / $diasCama * 100, 1) : 0,
                'saidas' => $saidas,
                'demoraMedia' => round($episodiosPeriodo->avg('dias_internamento') ?? 0, 1),
                'mortalidade' => $saidas ? round($obitos / $saidas * 100, 1) : 0,
                'diasAcimaLotacao' => $diasAcimaLotacaoPeriodo,
            ];
        })->filter()->values();

        // -----------------------------------------------------------------
        // Saídas por dia da semana
        // -----------------------------------------------------------------
        $diasSemanaLabels = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo'];
        $saidasPorDiaSemana = $episodios
            ->groupBy(fn($e) => (Carbon::parse($e->data_saida)->dayOfWeekIso - 1))
            ->map(fn($grupo, $idx) => ['dia' => $diasSemanaLabels[$idx], 'total' => $grupo->count()])
            ->values()
            ->sortBy(fn($r) => array_search($r['dia'], $diasSemanaLabels))
            ->values();

        // garantir que todos os dias aparecem, mesmo sem dados
        $saidasPorDiaSemana = collect($diasSemanaLabels)->map(function ($dia) use ($saidasPorDiaSemana) {
            return $saidasPorDiaSemana->firstWhere('dia', $dia) ?? ['dia' => $dia, 'total' => 0];
        })->values();

        // -----------------------------------------------------------------
        // Faixa etária — episódios, mortalidade e altas com atraso
        // -----------------------------------------------------------------
        $comIdade = $episodios->filter(fn($e) => $e->patient?->data_nascimento)->map(function ($e) {
            $idade = $this->idadeEm($e->patient->data_nascimento, $e->data_entrada);
            $e->faixa = $this->faixaEtaria($idade);
            return $e;
        });

        $porFaixaEtaria = collect(self::FAIXAS_IDADE)->map(function ($faixa) use ($comIdade) {
            $grupo = $comIdade->where('faixa', $faixa);
            $total = $grupo->count();
            $obitos = $grupo->where('falecido', true)->count();
            $comAtraso = $grupo->filter(fn($e) => $this->saiDepoisDaAltaClinica($e))->count();

            return [
                'faixa' => $faixa,
                'episodios' => $total,
                'mortalidade' => $total ? round($obitos / $total * 100, 1) : 0,
                'pctAltaComAtraso' => $total ? round($comAtraso / $total * 100, 1) : 0,
            ];
        })->values();

        // -----------------------------------------------------------------
        // Urgentes vs. Programados
        // -----------------------------------------------------------------
        $urgentes = $episodios->filter(fn($e) => $e->origem?->nome === 'Urgência');
        $programados = $episodios->reject(fn($e) => $e->origem?->nome === 'Urgência');

        $perfilSegmento = function ($grupo) use ($totalEpisodios) {
            $total = $grupo->count();
            $obitos = $grupo->where('falecido', true)->count();
            $dias = $grupo->pluck('dias_internamento')->filter(fn($d) => $d !== null)->values();

            return [
                'episodios' => $total,
                'pct' => $totalEpisodios ? round($total / $totalEpisodios * 100, 1) : 0,
                'demoraMedia' => $total ? round($dias->avg() ?? 0, 1) : 0,
                'demoraMediana' => $this->mediana($dias->all()),
                'mortalidade' => $total ? round($obitos / $total * 100, 1) : 0,
            ];
        };

        $urgentesProgramados = [
            'urgentes' => $perfilSegmento($urgentes),
            'programados' => $perfilSegmento($programados),
        ];

        // -----------------------------------------------------------------
        // Episódios por proveniência (origem)
        // -----------------------------------------------------------------
        $porProveniencia = $episodios
            ->groupBy(fn($e) => $e->origem?->nome ?? 'Sem origem')
            ->map(function ($grupo, $nome) use ($totalEpisodios) {
                $total = $grupo->count();
                $obitos = $grupo->where('falecido', true)->count();
                return [
                    'nome' => $nome,
                    'total' => $total,
                    'pct' => $totalEpisodios ? round($total / $totalEpisodios * 100, 1) : 0,
                    'demoraMedia' => round($grupo->avg('dias_internamento') ?? 0, 1),
                    'mortalidade' => $total ? round($obitos / $total * 100, 1) : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        // -----------------------------------------------------------------
        // Óbitos e readmissões
        // -----------------------------------------------------------------
        $obitosEpisodios = $episodios->where('falecido', true);
        $obitosComIdade = $obitosEpisodios->filter(fn($e) => $e->patient?->data_nascimento)
            ->map(fn($e) => $this->idadeEm($e->patient->data_nascimento, $e->data_entrada));

        $obitos = [
            'total' => $obitosEpisodios->count(),
            'pct' => $totalEpisodios ? round($obitosEpisodios->count() / $totalEpisodios * 100, 1) : 0,
            'idadeMedia' => $obitosComIdade->count() ? round($obitosComIdade->avg(), 1) : 0,
            'pctUrgentes' => $obitosEpisodios->count()
                ? round($obitosEpisodios->filter(fn($e) => $e->origem?->nome === 'Urgência')->count() / $obitosEpisodios->count() * 100, 1)
                : 0,
            'pctPrimeiros2Dias' => $obitosEpisodios->count()
                ? round($obitosEpisodios->filter(fn($e) => ($e->dias_internamento ?? 99) <= 2)->count() / $obitosEpisodios->count() * 100, 1)
                : 0,
        ];

        // -----------------------------------------------------------------
        // Grupos de diagnóstico mais frequentes (diagnóstico principal)
        // -----------------------------------------------------------------
        $internamentoIds = $episodios->pluck('id');

        $diagnosticoCounts = DB::table('diagnostico_internamento')
            ->join('diagnosticos', 'diagnosticos.id', '=', 'diagnostico_internamento.diagnostico_id')
            ->leftJoin('grupo_diagnosticos', 'grupo_diagnosticos.id', '=', 'diagnosticos.grupo_diagnostico_id')
            ->whereIn('diagnostico_internamento.internamento_id', $internamentoIds)
            ->where('diagnostico_internamento.principal', true)
            ->selectRaw("
                COALESCE(grupo_diagnosticos.nome, diagnosticos.nome) as grupo,
                internamento_id,
                diagnosticos.id as diagnostico_id
            ")
            ->get();

        $totalComDiagnostico = $diagnosticoCounts->pluck('internamento_id')->unique()->count();

        $gruposDiagnostico = $diagnosticoCounts
            ->groupBy('grupo')
            ->map(fn($g, $nome) => ['nome' => $nome, 'total' => $g->count()])
            ->sortByDesc('total')
            ->values();

        $topGruposDiagnostico = $gruposDiagnostico->take(10)->map(fn($g) => [
            ...$g,
            'pct' => $totalComDiagnostico ? round($g['total'] / $totalComDiagnostico * 100, 1) : 0,
        ])->values();

        // -----------------------------------------------------------------
        // Urgentes vs. Programados por grupo de diagnóstico (top 10)
        // -----------------------------------------------------------------
        $idsUrgentes = $urgentes->pluck('id');
        $gruposPorInternamento = $diagnosticoCounts->groupBy('internamento_id')->map(fn($g) => $g->first()->grupo);
        $episodiosPorId = $episodios->keyBy('id');

        $diagnosticoUrgenteProgramado = $topGruposDiagnostico->map(function ($grupoInfo) use ($gruposPorInternamento, $episodiosPorId, $idsUrgentes) {
            $idsDoGrupo = $gruposPorInternamento->filter(fn($g) => $g === $grupoInfo['nome'])->keys();

            $resumoSegmento = function ($ids) use ($episodiosPorId) {
                $dias = $ids->map(fn($id) => $episodiosPorId[$id]?->dias_internamento ?? null)->filter(fn($d) => $d !== null);
                return [
                    'episodios' => $ids->count(),
                    'demoraMedia' => $dias->count() ? round($dias->avg(), 1) : 0,
                ];
            };

            $idsUrg = $idsDoGrupo->intersect($idsUrgentes);
            $idsProg = $idsDoGrupo->diff($idsUrgentes);

            return [
                'nome' => $grupoInfo['nome'],
                'total' => $grupoInfo['total'],
                'demoraMedia' => $resumoSegmento($idsDoGrupo)['demoraMedia'],
                'urgentes' => $resumoSegmento($idsUrg),
                'programados' => $resumoSegmento($idsProg),
            ];
        })->values();

        $recomendacoes = $this->gerarRecomendacoes($anoNumeros, $urgentesProgramados, $readmissoes, $obitos, $porFaixaEtaria);

        return [
            'periodo' => [
                'data_inicio' => $dataInicio->toDateString(),
                'data_fim' => $dataFim->toDateString(),
            ],
            'lotacoes' => $lotacoes,
            'anoNumeros' => $anoNumeros,
            'porMes' => $porMes,
            'porPeriodoLotacao' => $porPeriodoLotacao,
            'saidasPorDiaSemana' => $saidasPorDiaSemana,
            'porFaixaEtaria' => $porFaixaEtaria,
            'urgentesProgramados' => $urgentesProgramados,
            'porProveniencia' => $porProveniencia,
            'obitos' => $obitos,
            'readmissoes' => $readmissoes,
            'gruposDiagnostico' => $topGruposDiagnostico,
            'diagnosticoUrgenteProgramado' => $diagnosticoUrgenteProgramado,
            'recomendacoes' => $recomendacoes,
        ];
    }

    /**
     * Sugestões de ação geradas por regras simples sobre os indicadores já
     * calculados — leitura automática, não clínica. Cada limiar está
     * documentado no texto da própria recomendação (nível: alerta > aviso >
     * info).
     *
     * @return array<int, array{nivel: string, titulo: string, texto: string}>
     */
    private function gerarRecomendacoes(array $anoNumeros, array $urgentesProgramados, array $readmissoes, array $obitos, $porFaixaEtaria): array
    {
        $recomendacoes = [];

        if ($anoNumeros['pctDiasAcimaLotacao'] >= 20) {
            $recomendacoes[] = [
                'nivel' => 'alerta',
                'titulo' => 'Lotação insuficiente para o censo real',
                'texto' => "O serviço esteve acima da lotação configurada em {$anoNumeros['pctDiasAcimaLotacao']}% dos dias ({$anoNumeros['diasAcimaLotacao']} dias), com um pico de {$anoNumeros['picoDoentesSemCama']} doentes sem cama própria. Vale a pena rever se o nº de camas em \"Lotação\" corresponde à capacidade real, ou planear camas extra/circuitos alternativos para os picos.",
            ];
        } elseif ($anoNumeros['taxaOcupacao'] >= 95) {
            $recomendacoes[] = [
                'nivel' => 'aviso',
                'titulo' => 'Ocupação no limite',
                'texto' => "Taxa de ocupação de {$anoNumeros['taxaOcupacao']}%, muito perto do limite da lotação — pouca margem para picos de admissões sem recorrer a camas extra.",
            ];
        }

        if ($anoNumeros['diaCamaVazia'] <= 0.5) {
            $recomendacoes[] = [
                'nivel' => 'aviso',
                'titulo' => 'Rotatividade de camas muito apertada',
                'texto' => "Em média, uma cama fica livre apenas {$anoNumeros['diaCamaVazia']} dia(s) entre doentes. Qualquer atraso na alta (limpeza, transporte, burocracia) tem impacto direto na admissão seguinte.",
            ];
        }

        if ($anoNumeros['readmissoes30dias'] >= 10) {
            $recomendacoes[] = [
                'nivel' => 'alerta',
                'titulo' => 'Readmissões a 30 dias elevadas',
                'texto' => "{$anoNumeros['readmissoes30dias']}% dos episódios são readmissões até 30 dias após uma alta anterior ({$anoNumeros['readmissoesPelaUrgencia']}% delas pela Urgência). Vale a pena rever critérios de alta e o seguimento pós-alta (consulta de follow-up, contacto telefónico) nos grupos de diagnóstico com mais peso.",
            ];
        }

        if ($anoNumeros['altasDepoisDaAltaClinica'] >= 2) {
            $recomendacoes[] = [
                'nivel' => 'aviso',
                'titulo' => 'Doentes ficam internados além da alta clínica',
                'texto' => "{$anoNumeros['altasDepoisDaAltaClinica']}% dos episódios têm saída real posterior à data de alta clínica registada — tipicamente motivos não clínicos (logística, apoio social, transporte). Reduzir este atraso liberta camas mais cedo sem qualquer intervenção clínica adicional.",
            ];
        }

        $mortalidadeUrgentes = $urgentesProgramados['urgentes']['mortalidade'];
        $mortalidadeProgramados = $urgentesProgramados['programados']['mortalidade'];
        if ($mortalidadeProgramados > 0 && $mortalidadeUrgentes >= $mortalidadeProgramados * 2) {
            $recomendacoes[] = [
                'nivel' => 'info',
                'titulo' => 'Mortalidade muito mais alta nos urgentes',
                'texto' => "Mortalidade de {$mortalidadeUrgentes}% nos episódios urgentes vs. {$mortalidadeProgramados}% nos programados. Esperado dado o perfil de gravidade, mas vale a pena confirmar se a referenciação/triagem inicial identifica precocemente os casos de maior risco.",
            ];
        }

        if ($obitos['pctPrimeiros2Dias'] >= 30) {
            $recomendacoes[] = [
                'nivel' => 'info',
                'titulo' => 'Muitos óbitos nos primeiros 2 dias',
                'texto' => "{$obitos['pctPrimeiros2Dias']}% dos óbitos ocorrem nos primeiros 2 dias de internamento — sugere doentes admitidos já em estado crítico, mais do que complicações desenvolvidas durante o internamento.",
            ];
        }

        $faixaMaisVelha = collect($porFaixaEtaria)->last();
        if ($faixaMaisVelha && $faixaMaisVelha['mortalidade'] >= 10) {
            $recomendacoes[] = [
                'nivel' => 'info',
                'titulo' => 'Mortalidade concentrada nos doentes mais idosos',
                'texto' => "A faixa etária {$faixaMaisVelha['faixa']} tem mortalidade de {$faixaMaisVelha['mortalidade']}%, claramente acima das restantes faixas — pode justificar um circuito ou protocolo específico para doentes idosos frágeis.",
            ];
        }

        if (empty($recomendacoes)) {
            $recomendacoes[] = [
                'nivel' => 'info',
                'titulo' => 'Sem desvios relevantes identificados',
                'texto' => 'Os indicadores calculados para este período não ultrapassam os limiares definidos para alerta ou aviso.',
            ];
        }

        $ordem = ['alerta' => 0, 'aviso' => 1, 'info' => 2];
        usort($recomendacoes, fn($a, $b) => $ordem[$a['nivel']] <=> $ordem[$b['nivel']]);

        return $recomendacoes;
    }

    /**
     * Dias-doente (soma de dias internado, por episódio) entre duas datas,
     * contando os episódios fornecidos (já pré-filtrados a abranger o
     * período pedido). Episódios ainda sem data_saida são censurados em
     * $fim.
     *
     * O dia da alta NÃO conta como dia ocupado — a cama fica disponível
     * nesse mesmo dia para outro doente. Esta é também a convenção da fonte
     * (SONHO/SClínico): `dias_internamento` = data_saida − data_entrada,
     * sem +1 (confirmado por amostragem). Por isso o overlap usa um
     * intervalo semiaberto [entrada, saída).
     */
    private function diasDoenteEntre($episodios, Carbon $inicio, Carbon $fim): float
    {
        $total = 0;
        $fimExclusivo = $fim->copy()->startOfDay()->addDay();

        foreach ($episodios as $e) {
            $entrada = Carbon::parse($e->data_entrada)->startOfDay();
            $saida = $e->data_saida ? Carbon::parse($e->data_saida)->startOfDay() : $fimExclusivo;

            $overlapInicio = $entrada->greaterThan($inicio) ? $entrada : $inicio->copy()->startOfDay();
            $overlapFimExclusivo = $saida->lessThan($fimExclusivo) ? $saida : $fimExclusivo;

            if ($overlapFimExclusivo->greaterThan($overlapInicio)) {
                $total += $overlapInicio->diffInDays($overlapFimExclusivo);
            }
        }

        return $total;
    }

    /**
     * `Internamento` não define casts de data (são strings em bruto), por
     * isso todas as comparações de datas neste relatório passam por
     * Carbon::parse() explícito em vez de confiar em casts automáticos.
     */
    private function saiDepoisDaAltaClinica(Internamento $e): bool
    {
        if (!$e->data_alta || !$e->data_saida) {
            return false;
        }

        return Carbon::parse($e->data_alta)->lt(Carbon::parse($e->data_saida));
    }

    private function idadeEm(string $dataNascimento, string $dataReferencia): int
    {
        return (int) floor(Carbon::parse($dataNascimento)->diffInYears(Carbon::parse($dataReferencia)));
    }

    /**
     * Censo diário (nº de doentes internados) vs. lotação, dia a dia, entre
     * $dataInicio e $dataFim. Usa um "sweep" de eventos (+1 na entrada, -1
     * na saída) em vez de contar episódio a episódio por dia — mais rápido
     * para períodos longos. A saída é exclusiva (mesma convenção de
     * diasDoenteEntre): o doente deixa de contar no censo no próprio dia da
     * alta.
     *
     * @return array<int, array{data: string, censo: int, lotacao: int}>
     */
    private function censoDiario($episodiosAbertos, $lotacoes, Carbon $dataInicio, Carbon $dataFim): array
    {
        $totalDias = $dataInicio->diffInDays($dataFim) + 1;
        $delta = array_fill(0, $totalDias + 1, 0);

        foreach ($episodiosAbertos as $e) {
            $entrada = Carbon::parse($e->data_entrada)->startOfDay();
            $saida = $e->data_saida ? Carbon::parse($e->data_saida)->startOfDay() : $dataFim->copy()->addDay();

            $inicioOffset = max(0, $dataInicio->diffInDays($entrada, false));
            $fimOffset = min($totalDias, $dataInicio->diffInDays($saida, false));

            if ($fimOffset > $inicioOffset) {
                $delta[$inicioOffset]++;
                $delta[$fimOffset]--;
            }
        }

        $serie = [];
        $acc = 0;
        $dia = $dataInicio->copy();

        for ($i = 0; $i < $totalDias; $i++) {
            $acc += $delta[$i];

            $lot = $lotacoes->first(
                fn($l) => $dia->greaterThanOrEqualTo($l->data_inicio) && $dia->lessThanOrEqualTo($l->data_fim ?? $dataFim)
            );

            $serie[] = ['data' => $dia->toDateString(), 'censo' => $acc, 'lotacao' => $lot?->numero_camas ?? 0];
            $dia->addDay();
        }

        return $serie;
    }

    private function faixaEtaria(int $idade): ?string
    {
        return match (true) {
            $idade < 18 => null,
            $idade <= 44 => '18-44',
            $idade <= 64 => '45-64',
            $idade <= 74 => '65-74',
            $idade <= 84 => '75-84',
            default => '85+',
        };
    }

    private function mediana(array $valores): float
    {
        sort($valores);
        $n = count($valores);
        if ($n === 0) {
            return 0;
        }
        $meio = intdiv($n, 2);
        return $n % 2 ? (float) $valores[$meio] : round(($valores[$meio - 1] + $valores[$meio]) / 2, 1);
    }

    /**
     * Readmissões a 30 dias: para cada doente com episódios no período,
     * usa o histórico completo (incluindo fora do período) para detectar o
     * episódio imediatamente anterior e medir o intervalo.
     */
    private function calcularReadmissoes($episodiosPeriodo, Carbon $dataInicio, Carbon $dataFim): array
    {
        $patientIds = $episodiosPeriodo->pluck('patient_id')->unique()->filter();

        if ($patientIds->isEmpty()) {
            return ['total30' => 0, 'pct30' => 0, 'totalSemana' => 0, 'totalUrgencia' => 0, 'pctUrgencia' => 0];
        }

        $historico = Internamento::query()
            ->whereIn('patient_id', $patientIds)
            ->with('origem')
            ->orderBy('patient_id')
            ->orderBy('data_entrada')
            ->get(['id', 'patient_id', 'data_entrada', 'data_saida', 'origem_id']);

        $total30 = 0;
        $totalSemana = 0;
        $totalUrgencia = 0;

        foreach ($historico->groupBy('patient_id') as $episodiosDoDoente) {
            $episodiosDoDoente = $episodiosDoDoente->values();

            for ($i = 1; $i < $episodiosDoDoente->count(); $i++) {
                $anterior = $episodiosDoDoente[$i - 1];
                $atual = $episodiosDoDoente[$i];

                if (!$anterior->data_saida || !$atual->data_entrada) {
                    continue;
                }

                $entradaAtual = Carbon::parse($atual->data_entrada);

                // só conta como evento deste relatório se a readmissão
                // (o episódio atual) ocorreu dentro do período pedido
                if ($entradaAtual->lt($dataInicio) || $entradaAtual->gt($dataFim)) {
                    continue;
                }

                $gap = Carbon::parse($anterior->data_saida)->diffInDays($entradaAtual, false);

                if ($gap >= 0 && $gap <= 30) {
                    $total30++;
                    if ($gap <= 7) {
                        $totalSemana++;
                    }
                    if ($atual->origem?->nome === 'Urgência') {
                        $totalUrgencia++;
                    }
                }
            }
        }

        $totalEpisodiosPeriodo = $episodiosPeriodo->count();

        return [
            'total30' => $total30,
            'pct30' => $totalEpisodiosPeriodo ? round($total30 / $totalEpisodiosPeriodo * 100, 1) : 0,
            'totalSemana' => $totalSemana,
            'totalUrgencia' => $totalUrgencia,
            'pctUrgencia' => $totalEpisodiosPeriodo ? round($totalUrgencia / $totalEpisodiosPeriodo * 100, 1) : 0,
        ];
    }
}
