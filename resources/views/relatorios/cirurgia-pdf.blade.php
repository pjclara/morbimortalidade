<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Internamento de Cirurgia: movimento assistencial</title>
    <style>
        @page { margin: 22px 26px; }

        * { box-sizing: border-box; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #17241F;
            font-size: 10px;
        }

        h1 { font-size: 15px; margin: 0 0 2px; color: #17241F; }
        h2 {
            font-size: 9px;
            margin: 0 0 8px;
            color: #6B776F;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        h3 { font-size: 10.5px; margin: 0 0 2px; color: #17241F; }

        p { margin: 0; }
        .muted { color: #6B776F; }
        .rose { color: #A32F26; }
        .small { font-size: 8.5px; }

        .header {
            border-bottom: 1.5px solid #1E6F5C;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .section {
            margin-bottom: 14px;
        }

        .kpi-table { width: 100%; border-collapse: separate; border-spacing: 4px; }
        .kpi-table td {
            border: 1px solid #DCE1DC;
            border-radius: 3px;
            padding: 6px 8px;
            width: 25%;
            vertical-align: top;
        }
        .kpi-label { font-size: 8px; color: #6B776F; }
        .kpi-value { font-size: 15px; font-weight: bold; color: #17241F; margin-top: 2px; }
        .kpi-value.rose { color: #A32F26; }
        .kpi-suffix { font-size: 10px; font-weight: normal; color: #6B776F; }
        .kpi-sub { font-size: 7.5px; color: #6B776F; margin-top: 1px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th {
            text-align: left;
            font-size: 8px;
            color: #6B776F;
            border-bottom: 1px solid #DCE1DC;
            padding: 3px 5px;
            font-weight: normal;
        }
        table.data td {
            font-size: 9px;
            border-bottom: 1px solid #EDEFEC;
            padding: 3px 5px;
            color: #17241F;
        }

        .bar-row td { padding: 2px 5px; }
        .bar-label { width: 38%; font-size: 8.5px; }
        .bar-track { width: 42%; }
        .bar-outer { background-color: #E3EFEA; border-radius: 3px; height: 7px; width: 100%; }
        .bar-inner { background-color: #1E6F5C; border-radius: 3px; height: 7px; }
        .bar-inner.rose { background-color: #A32F26; }
        .bar-inner.blue { background-color: #2a78d6; }
        .bar-value { width: 20%; text-align: right; font-size: 8.5px; white-space: nowrap; }

        .cols2 { width: 100%; }
        .cols2 td { width: 50%; vertical-align: top; padding-right: 10px; }

        .pill {
            display: inline-block;
            background-color: #E3EFEA;
            border-radius: 3px;
            padding: 6px 8px;
            width: 47%;
            margin-right: 2%;
        }

        .footer { margin-top: 10px; font-size: 7.5px; color: #6B776F; }

        .page-break { page-break-before: always; }

        .nota { font-size: 7.5px; color: #6B776F; margin-top: 3px; line-height: 1.35; }
        .nota b { color: #17241F; }

        .glossario dt { font-size: 9px; font-weight: bold; color: #17241F; margin-top: 7px; }
        .glossario dd { font-size: 8.5px; color: #45524C; margin: 1px 0 0; line-height: 1.4; }

        .recomendacao {
            border-left: 3px solid #DCE1DC;
            padding: 5px 0 5px 8px;
            margin-bottom: 6px;
        }
        .recomendacao.alerta { border-left-color: #A32F26; }
        .recomendacao.aviso { border-left-color: #C97A1E; }
        .recomendacao.info { border-left-color: #1E6F5C; }
        .recomendacao-titulo { font-size: 9px; font-weight: bold; color: #17241F; }
        .recomendacao-nivel { font-size: 7px; text-transform: uppercase; margin-left: 4px; }
        .recomendacao-nivel.alerta { color: #A32F26; }
        .recomendacao-nivel.aviso { color: #C97A1E; }
        .recomendacao-nivel.info { color: #1E6F5C; }
        .recomendacao-texto { font-size: 8.5px; color: #45524C; margin-top: 2px; line-height: 1.4; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Internamento de Cirurgia: movimento assistencial</h1>
        <p class="muted">Doentes saídos de {{ \Carbon\Carbon::parse($periodo['data_inicio'])->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($periodo['data_fim'])->format('d/m/Y') }}</p>
        <p class="small muted">
            @if($lotacoes->count())
                Lotação:
                @foreach($lotacoes as $lot)
                    {{ $lot->numero_camas }} camas ({{ $lot->observacoes ?: ($lot->data_inicio->format('d/m/Y') . '–' . ($lot->data_fim ? $lot->data_fim->format('d/m/Y') : 'atual')) }}){{ !$loop->last ? ',' : '' }}
                @endforeach
            @else
                Sem períodos de lotação configurados.
            @endif
        </p>
    </div>

    {{-- O período em números --}}
    <div class="section">
        <h2>O período em números</h2>
        <table class="kpi-table">
            <tr>
                <td>
                    <p class="kpi-label">Episódios de internamento</p>
                    <p class="kpi-value">{{ number_format($anoNumeros['episodios'], 0, ',', ' ') }}</p>
                </td>
                <td>
                    <p class="kpi-label">Demora média</p>
                    <p class="kpi-value">{{ $anoNumeros['demoraMedia'] }} <span class="kpi-suffix">dias</span></p>
                    <p class="kpi-sub">{{ $anoNumeros['pctAteTresDias'] }}% das estadias até 3 dias</p>
                </td>
                <td>
                    <p class="kpi-label">Taxa de ocupação</p>
                    <p class="kpi-value">{{ $anoNumeros['taxaOcupacao'] }}<span class="kpi-suffix">%</span></p>
                </td>
                <td>
                    <p class="kpi-label">Mortalidade</p>
                    <p class="kpi-value rose">{{ $anoNumeros['mortalidade'] }}<span class="kpi-suffix">%</span></p>
                    <p class="kpi-sub">óbitos / episódios</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p class="kpi-label">Doentes por cama</p>
                    <p class="kpi-value">{{ $anoNumeros['doentesPorCama'] }}</p>
                    <p class="kpi-sub">{{ $anoNumeros['doentesDistintos'] }} doentes distintos no período</p>
                </td>
                <td>
                    <p class="kpi-label">Dia de cama vazia</p>
                    <p class="kpi-value">{{ $anoNumeros['diaCamaVazia'] }} <span class="kpi-suffix">dias</span></p>
                    <p class="kpi-sub">entre doentes, por alta</p>
                </td>
                <td>
                    <p class="kpi-label">Readmissões a 30 dias</p>
                    <p class="kpi-value">{{ $anoNumeros['readmissoes30dias'] }}<span class="kpi-suffix">%</span></p>
                    <p class="kpi-sub">{{ $anoNumeros['readmissoesPelaUrgencia'] }}% pela Urgência</p>
                </td>
                <td>
                    <p class="kpi-label">Altas depois da alta clínica</p>
                    <p class="kpi-value">{{ $anoNumeros['altasDepoisDaAltaClinica'] }}<span class="kpi-suffix">%</span></p>
                </td>
            </tr>
            <tr>
                <td>
                    <p class="kpi-label">Doentes à espera de cama</p>
                    <p class="kpi-value rose">{{ $anoNumeros['pctDiasAcimaLotacao'] }}<span class="kpi-suffix">% dos dias</span></p>
                    <p class="kpi-sub">{{ $anoNumeros['diasAcimaLotacao'] }} dias acima da lotação</p>
                </td>
                <td>
                    <p class="kpi-label">Pico sem cama própria</p>
                    <p class="kpi-value rose">{{ $anoNumeros['picoDoentesSemCama'] }}</p>
                    <p class="kpi-sub">média de {{ $anoNumeros['mediaDoentesSemCamaNosDiasAcima'] }} nos dias acima da lotação</p>
                </td>
            </tr>
        </table>
        <p class="nota">Ver definição de cada indicador no glossário, no fim do documento.</p>
    </div>

    {{-- Doentes por mês + tabela por período de lotação --}}
    <div class="section">
        <h2>Doentes internados por dia (média mensal) e lotação</h2>
        @php $maxMes = max(1, ...collect($porMes)->map(fn($m) => max($m['doentesMedia'], $m['lotacao'] ?? 0))->all()); @endphp
        <table class="data">
            <tr>
                <th style="width: 12%;">Mês</th>
                <th style="width: 44%;">Doentes internados (média)</th>
                <th style="width: 44%;">Lotação</th>
            </tr>
            @foreach($porMes as $m)
                <tr class="bar-row">
                    <td>{{ $m['mes'] }}</td>
                    <td>
                        <table style="width:100%;"><tr>
                            <td class="bar-track"><div class="bar-outer"><div class="bar-inner" style="width: {{ max(2, $m['doentesMedia'] / $maxMes * 100) }}%;"></div></div></td>
                            <td class="bar-value">{{ $m['doentesMedia'] }}</td>
                        </tr></table>
                    </td>
                    <td>
                        <table style="width:100%;"><tr>
                            <td class="bar-track"><div class="bar-outer"><div class="bar-inner blue" style="width: {{ $m['lotacao'] ? max(2, $m['lotacao'] / $maxMes * 100) : 0 }}%;"></div></div></td>
                            <td class="bar-value">{{ $m['lotacao'] ?? '—' }}</td>
                        </tr></table>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <h2>Por período de lotação</h2>
        <table class="data">
            <tr>
                <th>Período</th>
                <th>Camas</th>
                <th>Doentes/dia</th>
                <th>Ocupação</th>
                <th>Saídas</th>
                <th>Demora média</th>
                <th>Mortalidade</th>
                <th>Dias acima lotação</th>
            </tr>
            @foreach($porPeriodoLotacao as $p)
                <tr>
                    <td>{{ $p['periodo'] }}</td>
                    <td>{{ $p['lotacao'] }}</td>
                    <td>{{ $p['doentesMedia'] }}</td>
                    <td>{{ $p['ocupacao'] }}%</td>
                    <td>{{ number_format($p['saidas'], 0, ',', ' ') }}</td>
                    <td>{{ $p['demoraMedia'] }}</td>
                    <td class="rose">{{ $p['mortalidade'] }}%</td>
                    <td class="rose">{{ $p['diasAcimaLotacao'] }}</td>
                </tr>
            @endforeach
            @if(count($porPeriodoLotacao) === 0)
                <tr><td colspan="8" class="muted">Sem períodos de lotação no intervalo seleccionado.</td></tr>
            @endif
        </table>
    </div>

    <div class="page-break"></div>

    {{-- Saídas por dia da semana + Urgentes vs Programados --}}
    <table class="cols2">
        <tr>
            <td>
                <div class="section">
                    <h2>Saídas por dia da semana</h2>
                    @php $maxDia = max(1, collect($saidasPorDiaSemana)->max('total')); @endphp
                    <table class="data">
                        @foreach($saidasPorDiaSemana as $d)
                            <tr class="bar-row">
                                <td class="bar-label">{{ $d['dia'] }}</td>
                                <td>
                                    <table style="width:100%;"><tr>
                                        <td class="bar-track"><div class="bar-outer"><div class="bar-inner" style="width: {{ max(2, $d['total'] / $maxDia * 100) }}%;"></div></div></td>
                                        <td class="bar-value">{{ $d['total'] }}</td>
                                    </tr></table>
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td>
                <div class="section">
                    <h2>Urgentes vs. Programados</h2>
                    <div class="pill">
                        <p class="small muted" style="text-transform:uppercase;">Urgentes</p>
                        <p class="kpi-value" style="font-size:12px;">{{ $urgentesProgramados['urgentes']['episodios'] }}
                            <span class="kpi-suffix">({{ $urgentesProgramados['urgentes']['pct'] }}%)</span></p>
                        <p class="small">Demora média: <b>{{ $urgentesProgramados['urgentes']['demoraMedia'] }} d</b> (mediana {{ $urgentesProgramados['urgentes']['demoraMediana'] }})</p>
                        <p class="small rose">Mortalidade: {{ $urgentesProgramados['urgentes']['mortalidade'] }}%</p>
                    </div>
                    <div class="pill">
                        <p class="small muted" style="text-transform:uppercase;">Programados</p>
                        <p class="kpi-value" style="font-size:12px;">{{ $urgentesProgramados['programados']['episodios'] }}
                            <span class="kpi-suffix">({{ $urgentesProgramados['programados']['pct'] }}%)</span></p>
                        <p class="small">Demora média: <b>{{ $urgentesProgramados['programados']['demoraMedia'] }} d</b> (mediana {{ $urgentesProgramados['programados']['demoraMediana'] }})</p>
                        <p class="small rose">Mortalidade: {{ $urgentesProgramados['programados']['mortalidade'] }}%</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Idade --}}
    <div class="section">
        <h2>Idade — episódios, mortalidade e altas com atraso por faixa etária</h2>
        <table class="data">
            <tr>
                <th>Faixa etária</th>
                <th>Episódios</th>
                <th>Mortalidade</th>
                <th>Altas com atraso</th>
            </tr>
            @foreach($porFaixaEtaria as $f)
                <tr>
                    <td>{{ $f['faixa'] }}</td>
                    <td>{{ $f['episodios'] }}</td>
                    <td class="rose">{{ $f['mortalidade'] }}%</td>
                    <td>{{ $f['pctAltaComAtraso'] }}%</td>
                </tr>
            @endforeach
        </table>
    </div>

    {{-- Proveniência + Óbitos/readmissões --}}
    <table class="cols2">
        <tr>
            <td>
                <div class="section">
                    <h2>Episódios por proveniência</h2>
                    <table class="data">
                        <tr><th>Origem</th><th>Total</th><th>%</th><th>Mortal.</th></tr>
                        @foreach($porProveniencia as $o)
                            <tr>
                                <td>{{ $o['nome'] }}</td>
                                <td>{{ $o['total'] }}</td>
                                <td>{{ $o['pct'] }}%</td>
                                <td class="rose">{{ $o['mortalidade'] }}%</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td>
                <div class="section">
                    <h2>Óbitos e readmissões</h2>
                    <table class="kpi-table">
                        <tr>
                            <td>
                                <p class="kpi-label">Óbitos</p>
                                <p class="kpi-value rose" style="font-size:13px;">{{ $obitos['total'] }}</p>
                                <p class="kpi-sub">{{ $obitos['pct'] }}% dos episódios</p>
                            </td>
                            <td>
                                <p class="kpi-label">Idade média dos óbitos</p>
                                <p class="kpi-value" style="font-size:13px;">{{ $obitos['idadeMedia'] }} <span class="kpi-suffix">anos</span></p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <p class="kpi-label">Readmissões a 30 dias</p>
                                <p class="kpi-value" style="font-size:13px;">{{ $readmissoes['pct30'] }}<span class="kpi-suffix">%</span></p>
                                <p class="kpi-sub">{{ $readmissoes['total30'] }} episódios</p>
                            </td>
                            <td>
                                <p class="kpi-label">Readm. pela Urgência</p>
                                <p class="kpi-value" style="font-size:13px;">{{ $readmissoes['pctUrgencia'] }}<span class="kpi-suffix">%</span></p>
                                <p class="kpi-sub">{{ $readmissoes['totalUrgencia'] }} episódios</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>

    {{-- Diagnósticos --}}
    <div class="section">
        <h2>Grupos de diagnóstico mais frequentes (diagnóstico principal)</h2>
        @php $maxDiag = max(1, collect($gruposDiagnostico)->max('total')); @endphp
        <table class="data">
            @foreach($gruposDiagnostico as $g)
                <tr class="bar-row">
                    <td class="bar-label">{{ $g['nome'] }}</td>
                    <td>
                        <table style="width:100%;"><tr>
                            <td class="bar-track"><div class="bar-outer"><div class="bar-inner" style="width: {{ max(2, $g['total'] / $maxDiag * 100) }}%;"></div></div></td>
                            <td class="bar-value">{{ $g['total'] }} ({{ $g['pct'] }}%)</td>
                        </tr></table>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <h2>Urgentes vs. Programados por grupo de diagnóstico</h2>
        <table class="data">
            <tr>
                <th>Grupo de diagnóstico</th>
                <th>Episódios (demora média)</th>
                <th>Urgentes</th>
                <th>Programados</th>
            </tr>
            @foreach($diagnosticoUrgenteProgramado as $d)
                <tr>
                    <td>{{ $d['nome'] }}</td>
                    <td>{{ $d['total'] }} ({{ $d['demoraMedia'] }} d)</td>
                    <td>{{ $d['urgentes']['episodios'] }} ({{ $d['urgentes']['demoraMedia'] }} d)</td>
                    <td>{{ $d['programados']['episodios'] }} ({{ $d['programados']['demoraMedia'] }} d)</td>
                </tr>
            @endforeach
        </table>
    </div>

    {{-- Síntese --}}
    <div class="section">
        <h2>Síntese sobre a eficiência</h2>
        <table class="cols2">
            <tr>
                <td>
                    <p class="small" style="color:#1E6F5C; text-transform:uppercase; font-weight:bold;">Pontos a destacar</p>
                    <ul style="margin: 4px 0 0 12px; padding: 0; font-size: 8.5px;">
                        <li>Ocupação de {{ $anoNumeros['taxaOcupacao'] }}% com {{ $anoNumeros['doentesPorCama'] }} doentes por cama e {{ $anoNumeros['diaCamaVazia'] }} dia(s) de cama vazia entre doentes.</li>
                        <li>Demora média de {{ $anoNumeros['demoraMedia'] }} dias; programados com {{ $urgentesProgramados['programados']['demoraMedia'] }} dias vs. {{ $urgentesProgramados['urgentes']['demoraMedia'] }} dias nos urgentes.</li>
                        <li>Mortalidade de {{ $anoNumeros['mortalidade'] }}% ({{ $urgentesProgramados['programados']['mortalidade'] }}% nos programados).</li>
                        <li>{{ round(100 - $anoNumeros['altasDepoisDaAltaClinica'], 1) }}% das altas saem no dia da alta clínica.</li>
                    </ul>
                </td>
                <td>
                    <p class="small" style="color:#A32F26; text-transform:uppercase; font-weight:bold;">Pontos de atenção</p>
                    <ul style="margin: 4px 0 0 12px; padding: 0; font-size: 8.5px;">
                        <li>Readmissões a 30 dias de {{ $anoNumeros['readmissoes30dias'] }}% ({{ $anoNumeros['readmissoesPelaUrgencia'] }}% pela Urgência).</li>
                        <li>{{ $anoNumeros['altasDepoisDaAltaClinica'] }}% das altas saem depois da alta clínica.</li>
                        <li>{{ $readmissoes['totalSemana'] }} readmissões ocorrem na primeira semana após a alta.</li>
                        <li>Óbitos: idade média de {{ $obitos['idadeMedia'] }} anos, {{ $obitos['pctPrimeiros2Dias'] }}% nos primeiros 2 dias de internamento.</li>
                    </ul>
                </td>
            </tr>
        </table>
    </div>

    {{-- Recomendações --}}
    <div class="section">
        <h2>Recomendações — leitura automática dos indicadores</h2>
        @foreach($recomendacoes as $r)
            <div class="recomendacao {{ $r['nivel'] }}">
                <p>
                    <span class="recomendacao-titulo">{{ $r['titulo'] }}</span>
                    <span class="recomendacao-nivel {{ $r['nivel'] }}">{{ strtoupper($r['nivel']) }}</span>
                </p>
                <p class="recomendacao-texto">{{ $r['texto'] }}</p>
            </div>
        @endforeach
        <p class="nota">Geradas por regras simples sobre os indicadores acima — não substituem avaliação clínica ou de gestão.</p>
    </div>

    <div class="page-break"></div>

    <div class="section">
        <h2>Glossário — como se obtém cada indicador</h2>
        <dl class="glossario">
            <dt>Episódios de internamento</dt>
            <dd>Nº de internamentos com data de saída dentro do período seleccionado.</dd>

            <dt>Demora média / % até 3 dias</dt>
            <dd>Média de dias internado por episódio; % até 3 dias = episódios com 3 dias de internamento ou menos.</dd>

            <dt>Taxa de ocupação</dt>
            <dd>Dias-doente (dias de internamento de cada episódio, com overlap ao período, incluindo episódios ainda sem alta) ÷ dias-cama disponíveis (nº de camas × dias do período, segundo os períodos configurados em Lotação).</dd>

            <dt>Mortalidade</dt>
            <dd>Óbitos (episódios marcados como "falecido") ÷ nº de episódios.</dd>

            <dt>Doentes por cama</dt>
            <dd>Nº de doentes distintos com episódios no período ÷ nº médio de camas no mesmo período.</dd>

            <dt>Dia de cama vazia</dt>
            <dd>(Dias-cama disponíveis − dias-doente) ÷ nº de saídas (episódios) — tempo médio de cama livre entre internamentos.</dd>

            <dt>Readmissões a 30 dias / pela Urgência</dt>
            <dd>% de episódios cuja data de entrada ocorre até 30 dias depois da data de saída do episódio anterior do mesmo doente. "Pela Urgência" é o subconjunto em que a origem da readmissão é Urgência.</dd>

            <dt>Altas depois da alta clínica</dt>
            <dd>% de episódios em que a data de saída real é posterior à data de alta clínica registada (ficou internado por razões não clínicas, ex. sociais).</dd>

            <dt>Doentes internados por dia (média mensal) / Lotação</dt>
            <dd>Censo médio diário = dias-doente do mês ÷ nº de dias do mês. Lotação = nº de camas registado em "Lotação" a meio de cada mês.</dd>

            <dt>Por período de lotação</dt>
            <dd>As mesmas fórmulas de ocupação, demora média e mortalidade, recalculadas apenas com os episódios cuja saída ocorre dentro de cada período definido em "Lotação".</dd>

            <dt>Doentes à espera de cama / Pico sem cama própria / Dias acima da lotação</dt>
            <dd>Calculado dia a dia: para cada dia do período, conta-se o censo real (doentes efetivamente internados nesse dia, com o dia da alta já excluído) e compara-se com a lotação desse dia. "Doentes à espera de cama" = % de dias em que o censo excede a lotação; nesses dias, o excesso são doentes sem cama própria atribuída (maca extra, corredor, etc., segundo a organização do serviço). "Pico sem cama própria" = maior excesso observado num único dia. "Dias acima da lotação" (na tabela por período) = quantos desses dias caem em cada período de lotação.</dd>

            <dt>Saídas por dia da semana</dt>
            <dd>Contagem de episódios por dia da semana da data de saída.</dd>

            <dt>Urgentes vs. Programados</dt>
            <dd>Segmentação pela origem do episódio: "Urgência" vs. todas as restantes origens (consulta externa, cirurgia de ambulatório, outro hospital, etc.), tratadas aqui como "programados".</dd>

            <dt>Idade (episódios, mortalidade, altas com atraso)</dt>
            <dd>Idade = anos entre a data de nascimento e a data de entrada do episódio (doentes com menos de 18 anos não entram nestas faixas). Mortalidade e altas com atraso calculadas dentro de cada faixa, com as mesmas fórmulas acima.</dd>

            <dt>Episódios por proveniência</dt>
            <dd>Agrupamento pela origem registada no episódio (Urgência, Consulta externa, Exterior, Outro hospital, etc.).</dd>

            <dt>Óbitos e readmissões</dt>
            <dd>Óbitos = episódios marcados como "falecido"; idade média calculada à data de entrada. "Óbitos nos 1os 2 dias" = óbitos com 2 dias de internamento ou menos. Readmissões conforme definido acima.</dd>

            <dt>Grupos de diagnóstico mais frequentes</dt>
            <dd>Diagnóstico principal de cada episódio, agrupado por "grupo de diagnóstico" (ou pelo nome do diagnóstico quando sem grupo associado); % sobre os episódios com diagnóstico principal registado.</dd>

            <dt>Urgentes vs. Programados por grupo de diagnóstico</dt>
            <dd>Os 10 grupos de diagnóstico mais frequentes (acima), repartidos pela origem de cada episódio.</dd>
        </dl>
    </div>

    <p class="footer">ULSRL | Sistema morbi 2.0 | Fonte: dados de internamento registados na aplicação. Gerado em {{ now()->format('d/m/Y H:i') }}.</p>

</body>
</html>
