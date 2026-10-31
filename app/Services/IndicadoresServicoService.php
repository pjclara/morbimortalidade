<?php

namespace App\Services;

use App\Models\Internamento;
use App\Models\Lotacao;
use App\Models\Origem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Indicadores do serviço para os 5 problemas identificados: altas ao
 * fim de semana, estadias longas, atraso após alta clínica, readmissões a
 * 30 dias e margem de camas. Segue o mesmo estilo de agregação já usado em
 * AnaliticaController/InternamentoController, mas isolado numa classe
 * testável (sem depender de HTTP).
 *
 * As agregações que dependem de intervalos de datas (dias entre duas datas,
 * "dia da semana") são feitas em PHP com Carbon sobre os registos já
 * filtrados por SQL, em vez de SQL específico de motor (MySQL em produção,
 * sqlite nos testes) — mesma razão do scope `readmissaoA30Dias`.
 */
class IndicadoresServicoService
{
    public function __construct(
        protected ?string $dataInicio = null,
        protected ?string $dataFim = null,
        protected ?int $equipaId = null,
    ) {
    }

    protected function queryBase()
    {
        $query = Internamento::query()->whereNotNull('data_saida');

        if ($this->dataInicio) {
            $query->where('data_saida', '>=', $this->dataInicio);
        }

        if ($this->dataFim) {
            $query->where('data_saida', '<=', $this->dataFim);
        }

        if ($this->equipaId) {
            $query->where('equipa_id', $this->equipaId);
        }

        return $query;
    }

    protected function idsOrigemUrgencia(): array
    {
        return Origem::where('nome', 'Urgência')->pluck('id')->all();
    }

    /**
     * % de altas ao sábado e domingo e distribuição por dia da semana
     * (índice Carbon: 0 = domingo ... 6 = sábado).
     */
    public function altasPorDiaSemana(): array
    {
        $internamentos = $this->queryBase()->get(['id', 'data_saida']);

        $porDiaSemana = array_fill(0, 7, 0);
        foreach ($internamentos as $internamento) {
            $porDiaSemana[Carbon::parse($internamento->data_saida)->dayOfWeek]++;
        }

        $total = $internamentos->count();
        $fimDeSemana = $porDiaSemana[Carbon::SATURDAY] + $porDiaSemana[Carbon::SUNDAY];

        return [
            'total' => $total,
            'por_dia_semana' => $porDiaSemana,
            'percentagem_sabado' => $total ? round($porDiaSemana[Carbon::SATURDAY] / $total * 100, 1) : 0.0,
            'percentagem_domingo' => $total ? round($porDiaSemana[Carbon::SUNDAY] / $total * 100, 1) : 0.0,
            'percentagem_fim_de_semana' => $total ? round($fimDeSemana / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Demora média (total, programados, urgentes) e estadias longas
     * (≥15 dias).
     */
    public function demoraMedia(): array
    {
        $todos = $this->queryBase()->get(['id', 'dias_internamento', 'origem_id']);
        $idsUrgencia = $this->idsOrigemUrgencia();

        $urgentes = $todos->filter(fn ($i) => in_array($i->origem_id, $idsUrgencia, true));
        $programados = $todos->reject(fn ($i) => in_array($i->origem_id, $idsUrgencia, true));

        $longas = $todos->filter(fn ($i) => $i->dias_internamento !== null && $i->dias_internamento >= 15);
        $total = $todos->count();

        return [
            'media_total' => round((float) $todos->avg('dias_internamento'), 1),
            'media_programados' => round((float) $programados->avg('dias_internamento'), 1),
            'media_urgentes' => round((float) $urgentes->avg('dias_internamento'), 1),
            'estadias_longas_n' => $longas->count(),
            'estadias_longas_percentagem' => $total ? round($longas->count() / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Dias totais de atraso após alta clínica, doentes com ≥7 dias e
     * distribuição por motivo.
     */
    public function atrasoAltaClinica(): array
    {
        $internamentos = $this->queryBase()->with('atrasosAlta.motivo')->get();

        $comAtraso = $internamentos->filter(fn (Internamento $i) => $i->dias_atraso_alta !== null);

        $porMotivo = [];
        foreach ($internamentos as $internamento) {
            foreach ($internamento->atrasosAlta as $atraso) {
                $nome = $atraso->motivo->nome ?? 'Sem motivo';
                $porMotivo[$nome] = ($porMotivo[$nome] ?? 0) + 1;
            }
        }

        return [
            'total_dias_atraso' => (int) $comAtraso->sum('dias_atraso_alta'),
            'doentes_com_atraso' => $comAtraso->count(),
            'doentes_7_ou_mais_dias' => $comAtraso->filter(fn (Internamento $i) => $i->dias_atraso_alta >= 7)->count(),
            'por_motivo' => $porMotivo,
        ];
    }

    /**
     * Readmissões a 30 dias: % total, % na primeira semana, planeadas vs
     * não planeadas, relacionadas vs não relacionadas.
     */
    public function readmissoes30Dias(): array
    {
        $total = (clone $this->queryBase())->count();
        $readmitidos = (clone $this->queryBase())->readmissaoA30Dias()->get();

        $primeiraSemana = 0;
        $planeadas = 0;
        $naoPlaneadas = 0;
        $relacionadas = 0;
        $naoRelacionadas = 0;

        foreach ($readmitidos as $internamento) {
            $indice = $internamento->internamentoIndice();

            if ($indice && Carbon::parse($indice->data_saida)->diffInDays(Carbon::parse($internamento->data_entrada)) < 7) {
                $primeiraSemana++;
            }

            match ($internamento->readmissao_planeada) {
                true => $planeadas++,
                false => $naoPlaneadas++,
                default => null,
            };

            match ($internamento->readmissao_relacionada) {
                true => $relacionadas++,
                false => $naoRelacionadas++,
                default => null,
            };
        }

        $n = $readmitidos->count();

        return [
            'total_internamentos' => $total,
            'n_readmissoes' => $n,
            'percentagem' => $total ? round($n / $total * 100, 1) : 0.0,
            'percentagem_primeira_semana' => $n ? round($primeiraSemana / $n * 100, 1) : 0.0,
            'planeadas' => $planeadas,
            'nao_planeadas' => $naoPlaneadas,
            'relacionadas' => $relacionadas,
            'nao_relacionadas' => $naoRelacionadas,
        ];
    }

    /**
     * Ocupação diária = doentes presentes / numero_camas da lotação em
     * vigor nesse dia; nº de dias acima da lotação; dia da semana com mais
     * entradas programadas (não-Urgência). Precisa de dataInicio/dataFim.
     */
    public function ocupacaoDiaria(): array
    {
        if (!$this->dataInicio || !$this->dataFim) {
            throw new \InvalidArgumentException('ocupacaoDiaria precisa de dataInicio e dataFim.');
        }

        $internamentosQuery = Internamento::whereNotNull('data_entrada')
            ->where('data_entrada', '<=', $this->dataFim)
            ->where(function ($q) {
                $q->whereNull('data_saida')->orWhere('data_saida', '>=', $this->dataInicio);
            });

        if ($this->equipaId) {
            $internamentosQuery->where('equipa_id', $this->equipaId);
        }

        $internamentos = $internamentosQuery->get(['id', 'data_entrada', 'data_saida', 'origem_id']);
        $lotacoes = Lotacao::orderBy('data_inicio')->get();
        $idsUrgencia = $this->idsOrigemUrgencia();

        $entradasPorDiaSemana = array_fill(0, 7, 0);
        foreach ($internamentos as $internamento) {
            $entrada = Carbon::parse($internamento->data_entrada);

            if (!in_array($internamento->origem_id, $idsUrgencia, true) && $entrada->between(Carbon::parse($this->dataInicio), Carbon::parse($this->dataFim))) {
                $entradasPorDiaSemana[$entrada->dayOfWeek]++;
            }
        }

        $ocupacaoPorDia = [];
        $diasAcimaLotacao = 0;

        foreach (CarbonPeriod::create($this->dataInicio, $this->dataFim) as $dia) {
            $presentes = $internamentos->filter(function ($internamento) use ($dia) {
                $entrada = Carbon::parse($internamento->data_entrada);
                $saida = $internamento->data_saida ? Carbon::parse($internamento->data_saida) : null;

                return $entrada->lte($dia) && (!$saida || $saida->gte($dia));
            })->count();

            $lotacaoDoDia = $lotacoes->first(function (Lotacao $l) use ($dia) {
                return Carbon::parse($l->data_inicio)->lte($dia)
                    && (!$l->data_fim || Carbon::parse($l->data_fim)->gte($dia));
            });

            $camas = $lotacaoDoDia?->numero_camas;

            if ($camas !== null && $presentes > $camas) {
                $diasAcimaLotacao++;
            }

            $ocupacaoPorDia[$dia->format('Y-m-d')] = [
                'presentes' => $presentes,
                'numero_camas' => $camas,
            ];
        }

        $diaComMaisEntradas = array_search(max($entradasPorDiaSemana), $entradasPorDiaSemana, true);

        return [
            'ocupacao_por_dia' => $ocupacaoPorDia,
            'dias_acima_lotacao' => $diasAcimaLotacao,
            'entradas_programadas_por_dia_semana' => $entradasPorDiaSemana,
            'dia_semana_mais_entradas_programadas' => $diaComMaisEntradas,
        ];
    }
}
