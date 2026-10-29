import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { useState } from 'react';
import { Bar, Line } from 'react-chartjs-2';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Movimento assistencial', href: '/relatorio-cirurgia' }];

// Mesmos tokens usados em Analitica/Index — papel frio, verde-cirúrgico como
// acento único, azul/laranja para pares a comparar, vermelho para mortalidade.
const INK = '#17241F';
const PAPER = '#F5F6F3';
const LINE = '#DCE1DC';
const MUTED = '#6B776F';
const CLINICAL = '#1E6F5C';
const CLINICAL_SOFT = '#E3EFEA';
const SERIES_BLUE = '#2a78d6';
const SERIES_ORANGE = '#eb6834';
const ROSE = '#A32F26';

const fieldLabel = 'text-xs font-medium tracking-normal text-[#45524C]';
const fieldInput =
    'rounded-[4px] border border-[#C9D1CB] bg-white px-2.5 py-1.5 text-sm text-[#17241F] outline-none transition focus:border-[#1E6F5C] focus:ring-2 focus:ring-[#1E6F5C]/20 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100';

interface Periodo {
    data_inicio: string;
    data_fim: string;
}

interface Lotacao {
    id: number;
    data_inicio: string;
    data_fim: string | null;
    numero_camas: number;
    observacoes: string | null;
}

interface AnoNumeros {
    episodios: number;
    demoraMedia: number;
    pctAteTresDias: number;
    taxaOcupacao: number;
    mortalidade: number;
    doentesPorCama: number;
    doentesDistintos: number;
    diaCamaVazia: number;
    readmissoes30dias: number;
    readmissoesPelaUrgencia: number;
    altasDepoisDaAltaClinica: number;
    diasAcimaLotacao: number;
    pctDiasAcimaLotacao: number;
    picoDoentesSemCama: number;
    mediaDoentesSemCamaNosDiasAcima: number;
}

interface MesLinha {
    mes: string;
    doentesMedia: number;
    lotacao: number | null;
}

interface PeriodoLotacaoLinha {
    periodo: string;
    lotacao: number;
    doentesMedia: number;
    ocupacao: number;
    saidas: number;
    demoraMedia: number;
    mortalidade: number;
    diasAcimaLotacao: number;
}

interface DiaSemanaLinha {
    dia: string;
    total: number;
}

interface FaixaEtariaLinha {
    faixa: string;
    episodios: number;
    mortalidade: number;
    pctAltaComAtraso: number;
}

interface SegmentoPerfil {
    episodios: number;
    pct: number;
    demoraMedia: number;
    demoraMediana: number;
    mortalidade: number;
}

interface ProvenienciaLinha {
    nome: string;
    total: number;
    pct: number;
    demoraMedia: number;
    mortalidade: number;
}

interface Obitos {
    total: number;
    pct: number;
    idadeMedia: number;
    pctUrgentes: number;
    pctPrimeiros2Dias: number;
}

interface Readmissoes {
    total30: number;
    pct30: number;
    totalSemana: number;
    totalUrgencia: number;
    pctUrgencia: number;
}

interface GrupoDiagnosticoLinha {
    nome: string;
    total: number;
    pct: number;
}

interface DiagnosticoUrgenteProgramadoLinha {
    nome: string;
    total: number;
    demoraMedia: number;
    urgentes: { episodios: number; demoraMedia: number };
    programados: { episodios: number; demoraMedia: number };
}

interface PageProps {
    periodo: Periodo;
    lotacoes: Lotacao[];
    anoNumeros: AnoNumeros;
    porMes: MesLinha[];
    porPeriodoLotacao: PeriodoLotacaoLinha[];
    saidasPorDiaSemana: DiaSemanaLinha[];
    porFaixaEtaria: FaixaEtariaLinha[];
    urgentesProgramados: { urgentes: SegmentoPerfil; programados: SegmentoPerfil };
    porProveniencia: ProvenienciaLinha[];
    obitos: Obitos;
    readmissoes: Readmissoes;
    gruposDiagnostico: GrupoDiagnosticoLinha[];
    diagnosticoUrgenteProgramado: DiagnosticoUrgenteProgramadoLinha[];
}

function fmt(n: number) {
    return (n ?? 0).toLocaleString('pt-PT');
}

function fmtData(iso: string) {
    if (!iso) return '';
    const [a, m, d] = iso.slice(0, 10).split('-');
    return `${d}/${m}/${a}`;
}

function KpiTile({ label, value, suffix, sub, tone }: { label: string; value: number | string; suffix?: string; sub?: string; tone?: 'default' | 'rose' }) {
    return (
        <div className="rounded-[6px] border bg-white p-4" style={{ borderColor: LINE }}>
            <p className="text-xs font-medium" style={{ color: MUTED }}>
                {label}
            </p>
            <p className="mt-1.5 text-2xl font-semibold" style={{ color: tone === 'rose' ? ROSE : INK }}>
                {typeof value === 'number' ? fmt(value) : value}
                {suffix && (
                    <span className="ml-0.5 text-base font-medium" style={{ color: MUTED }}>
                        {suffix}
                    </span>
                )}
            </p>
            {sub && (
                <p className="mt-0.5 text-xs" style={{ color: MUTED }}>
                    {sub}
                </p>
            )}
        </div>
    );
}

function BarList({
    rows,
    labelKey,
    valueKey,
    pctKey,
    accent = CLINICAL,
    emptyText = 'Sem dados no período seleccionado.',
    limit,
    suffix = '',
}: {
    rows: Record<string, any>[];
    labelKey: string;
    valueKey: string;
    pctKey?: string;
    accent?: string;
    emptyText?: string;
    limit?: number;
    suffix?: string;
}) {
    const data = limit ? rows.slice(0, limit) : rows;
    const max = Math.max(1, ...data.map((r) => Number(r[valueKey]) || 0));

    if (!data.length) {
        return (
            <p className="text-sm" style={{ color: MUTED }}>
                {emptyText}
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-2.5">
            {data.map((row, i) => {
                const value = Number(row[valueKey]) || 0;
                const barWidthPct = Math.max(2, (value / max) * 100);
                return (
                    <div key={i} className="grid grid-cols-[1fr_auto] items-center gap-3">
                        <div>
                            <p className="truncate text-sm" style={{ color: INK }} title={String(row[labelKey])}>
                                {row[labelKey] ?? '—'}
                            </p>
                            <div className="mt-1 h-1.5 w-full rounded-full" style={{ backgroundColor: CLINICAL_SOFT }}>
                                <div className="h-1.5 rounded-full transition-all" style={{ width: `${barWidthPct}%`, backgroundColor: accent }} />
                            </div>
                        </div>
                        <span className="text-sm font-medium tabular-nums" style={{ color: INK }}>
                            {fmt(value)}
                            {suffix}
                            {pctKey && (
                                <span className="ml-1 font-normal" style={{ color: MUTED }}>
                                    ({fmt(Number(row[pctKey]) || 0)}%)
                                </span>
                            )}
                        </span>
                    </div>
                );
            })}
        </div>
    );
}

function Nota({ children }: { children: React.ReactNode }) {
    return (
        <p className="mt-3 text-xs leading-relaxed" style={{ color: MUTED }}>
            <span className="font-medium" style={{ color: INK }}>
                Como se obtém:
            </span>{' '}
            {children}
        </p>
    );
}

function Section({ title, subtitle, children }: { title: string; subtitle?: string; children: React.ReactNode }) {
    return (
        <section className="rounded-[6px] border bg-white p-5" style={{ borderColor: LINE }}>
            <div className="mb-4">
                <h2 className="text-sm font-semibold" style={{ color: INK }}>
                    {title}
                </h2>
                {subtitle && (
                    <p className="text-xs" style={{ color: MUTED }}>
                        {subtitle}
                    </p>
                )}
            </div>
            {children}
        </section>
    );
}

export default function RelatorioCirurgia() {
    const {
        periodo,
        lotacoes = [],
        anoNumeros,
        porMes = [],
        porPeriodoLotacao = [],
        saidasPorDiaSemana = [],
        porFaixaEtaria = [],
        urgentesProgramados,
        porProveniencia = [],
        obitos,
        readmissoes,
        gruposDiagnostico = [],
        diagnosticoUrgenteProgramado = [],
    } = usePage<PageProps>().props;

    const [local, setLocal] = useState<Periodo>(periodo);

    function apply(next: Periodo) {
        setLocal(next);
        router.get('/relatorio-cirurgia', next, { preserveState: true, preserveScroll: true });
    }

    function exportarPdf() {
        const params = new URLSearchParams({
            data_inicio: local.data_inicio ?? '',
            data_fim: local.data_fim ?? '',
        });
        window.open(`/relatorio-cirurgia/pdf?${params.toString()}`, '_blank');
    }

    const descricaoLotacao = lotacoes
        .map((l) => `${l.numero_camas} camas (${l.observacoes || `${fmtData(l.data_inicio)}–${l.data_fim ? fmtData(l.data_fim) : 'atual'}`})`)
        .join(', ');

    const gridColor = LINE;
    const commonScales = {
        x: { grid: { display: false }, ticks: { color: MUTED, font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: MUTED, font: { size: 11 }, precision: 0 } },
    };

    const mesesChart = {
        labels: porMes.map((m) => m.mes),
        datasets: [
            {
                type: 'line' as const,
                label: 'Doentes internados (média)',
                data: porMes.map((m) => m.doentesMedia),
                borderColor: CLINICAL,
                backgroundColor: 'rgba(30,111,92,0.10)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: CLINICAL,
                pointBorderColor: PAPER,
                pointBorderWidth: 2,
                fill: true,
                tension: 0.3,
                yAxisID: 'y',
            },
            {
                type: 'line' as const,
                label: 'Lotação',
                data: porMes.map((m) => m.lotacao),
                borderColor: SERIES_ORANGE,
                backgroundColor: 'transparent',
                borderDash: [5, 4],
                borderWidth: 2,
                pointRadius: 0,
                tension: 0,
                yAxisID: 'y',
            },
        ],
    };

    const diaSemanaChart = {
        labels: saidasPorDiaSemana.map((d) => d.dia),
        datasets: [
            {
                label: 'Saídas',
                data: saidasPorDiaSemana.map((d) => d.total),
                backgroundColor: CLINICAL,
                borderRadius: 4,
                maxBarThickness: 36,
            },
        ],
    };

    const faixaEtariaChart = {
        labels: porFaixaEtaria.map((f) => f.faixa),
        datasets: [
            {
                label: 'Episódios',
                data: porFaixaEtaria.map((f) => f.episodios),
                backgroundColor: SERIES_BLUE,
                borderRadius: 4,
                maxBarThickness: 32,
                yAxisID: 'y',
            },
        ],
    };

    const mortalidadeEtariaChart = {
        labels: porFaixaEtaria.map((f) => f.faixa),
        datasets: [
            {
                label: 'Mortalidade (%)',
                data: porFaixaEtaria.map((f) => f.mortalidade),
                backgroundColor: ROSE,
                borderRadius: 4,
                maxBarThickness: 32,
            },
        ],
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Movimento assistencial" />

            <div className="flex flex-col gap-5 p-4 md:p-6" style={{ backgroundColor: PAPER }}>
                {/* Capa */}
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-lg font-semibold" style={{ color: INK }}>
                            Internamento de Cirurgia: movimento assistencial
                        </h1>
                        <p className="text-sm" style={{ color: MUTED }}>
                            Doentes saídos de {fmtData(periodo.data_inicio)} a {fmtData(periodo.data_fim)}
                        </p>
                        <p className="text-xs" style={{ color: MUTED }}>
                            {descricaoLotacao ? `Lotação: ${descricaoLotacao}` : 'Sem períodos de lotação configurados.'}
                        </p>
                    </div>

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="flex flex-col gap-1">
                            <label className={fieldLabel}>Data início</label>
                            <input
                                type="date"
                                className={fieldInput}
                                value={local.data_inicio ?? ''}
                                onChange={(e) => apply({ ...local, data_inicio: e.target.value })}
                            />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className={fieldLabel}>Data fim</label>
                            <input
                                type="date"
                                className={fieldInput}
                                value={local.data_fim ?? ''}
                                onChange={(e) => apply({ ...local, data_fim: e.target.value })}
                            />
                        </div>
                        <button
                            onClick={exportarPdf}
                            className="rounded-[4px] px-3 py-1.5 text-sm font-medium text-white transition hover:opacity-90"
                            style={{ backgroundColor: CLINICAL }}
                        >
                            Exportar PDF
                        </button>
                    </div>
                </div>

                {/* O ano em números */}
                <div>
                    <h2 className="mb-3 text-sm font-semibold tracking-wide uppercase" style={{ color: MUTED }}>
                        O período em números
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        <KpiTile label="Episódios de internamento" value={anoNumeros.episodios} />
                        <KpiTile label="Demora média" value={anoNumeros.demoraMedia} suffix="dias" sub={`${fmt(anoNumeros.pctAteTresDias)}% das estadias até 3 dias`} />
                        <KpiTile label="Taxa de ocupação" value={anoNumeros.taxaOcupacao} suffix="%" />
                        <KpiTile label="Mortalidade" value={anoNumeros.mortalidade} suffix="%" tone="rose" sub="óbitos / episódios" />
                        <KpiTile label="Doentes por cama" value={anoNumeros.doentesPorCama} sub={`${fmt(anoNumeros.doentesDistintos)} doentes distintos no período`} />
                        <KpiTile label="Dia de cama vazia" value={anoNumeros.diaCamaVazia} suffix="dias" sub="entre doentes, por alta" />
                        <KpiTile
                            label="Readmissões a 30 dias"
                            value={anoNumeros.readmissoes30dias}
                            suffix="%"
                            sub={`${fmt(anoNumeros.readmissoesPelaUrgencia)}% pela Urgência`}
                        />
                        <KpiTile label="Altas depois da alta clínica" value={anoNumeros.altasDepoisDaAltaClinica} suffix="%" />
                        <KpiTile
                            label="Doentes à espera de cama"
                            value={anoNumeros.pctDiasAcimaLotacao}
                            suffix="% dos dias"
                            tone="rose"
                            sub={`${fmt(anoNumeros.diasAcimaLotacao)} dias acima da lotação`}
                        />
                        <KpiTile
                            label="Pico sem cama própria"
                            value={anoNumeros.picoDoentesSemCama}
                            tone="rose"
                            sub={`média de ${fmt(anoNumeros.mediaDoentesSemCamaNosDiasAcima)} nos dias acima da lotação`}
                        />
                    </div>
                    <Nota>
                        <strong>Episódios</strong>: internamentos com data de saída dentro do período. <strong>Demora média</strong>: média de
                        dias internado por episódio (% até 3 dias = episódios com ≤3 dias). <strong>Taxa de ocupação</strong>: dias-doente ÷
                        dias-cama disponíveis (nº de camas × dias do período, segundo a Lotação). <strong>Mortalidade</strong>: óbitos ÷
                        episódios. <strong>Doentes por cama</strong>: doentes distintos no período ÷ nº médio de camas. <strong>Dia de cama
                        vazia</strong>: (dias-cama − dias-doente) ÷ nº de saídas. <strong>Readmissões a 30 dias</strong>: % de episódios cuja
                        entrada ocorre até 30 dias após a saída do episódio anterior do mesmo doente. <strong>Altas depois da alta
                        clínica</strong>: % de episódios em que a saída real é posterior à data de alta clínica registada.{' '}
                        <strong>Doentes à espera de cama</strong>: % de dias em que o censo diário (nº de doentes efetivamente internados
                        nesse dia) ultrapassa a lotação desse dia — esses doentes em excesso não têm cama própria atribuída (maca extra,
                        corredor, etc.). <strong>Pico sem cama própria</strong>: maior nº de doentes em excesso observado num único dia.
                    </Nota>
                </div>

                {/* Evolução mensal vs lotação + tabela por período */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Section title="Doentes internados por dia (média mensal) e lotação" subtitle="Censo médio diário, mês a mês, vs. nº de camas.">
                        <div className="h-64">
                            <Line
                                data={mesesChart as any}
                                options={{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: { position: 'top', align: 'end', labels: { color: INK, boxWidth: 10, boxHeight: 10, font: { size: 12 } } },
                                    },
                                    scales: commonScales,
                                }}
                            />
                        </div>
                        <Nota>
                            censo médio diário = dias-doente do mês (dias de internamento com overlap no mês, incluindo episódios ainda
                            sem alta) ÷ nº de dias do mês. Lotação = nº de camas registado em "Lotação" a meio de cada mês.
                        </Nota>
                    </Section>

                    <Section title="Por período de lotação" subtitle="Ocupação, saídas, demora média e mortalidade por período de lotação configurado.">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left" style={{ borderColor: LINE, color: MUTED }}>
                                        <th className="py-1.5 pr-3 font-medium">Período</th>
                                        <th className="py-1.5 pr-3 font-medium">Camas</th>
                                        <th className="py-1.5 pr-3 font-medium">Doentes/dia</th>
                                        <th className="py-1.5 pr-3 font-medium">Ocupação</th>
                                        <th className="py-1.5 pr-3 font-medium">Saídas</th>
                                        <th className="py-1.5 pr-3 font-medium">Demora média</th>
                                        <th className="py-1.5 pr-3 font-medium">Mortalidade</th>
                                        <th className="py-1.5 font-medium">Dias acima da lotação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {porPeriodoLotacao.map((p, i) => (
                                        <tr key={i} className="border-b" style={{ borderColor: LINE }}>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {p.periodo}
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {p.lotacao}
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {fmt(p.doentesMedia)}
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {fmt(p.ocupacao)}%
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {fmt(p.saidas)}
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {fmt(p.demoraMedia)}
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: ROSE }}>
                                                {fmt(p.mortalidade)}%
                                            </td>
                                            <td className="py-1.5" style={{ color: ROSE }}>
                                                {fmt(p.diasAcimaLotacao)}
                                            </td>
                                        </tr>
                                    ))}
                                    {porPeriodoLotacao.length === 0 && (
                                        <tr>
                                            <td colSpan={8} className="py-4 text-center" style={{ color: MUTED }}>
                                                Sem períodos de lotação no intervalo seleccionado.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Nota>
                            cada linha corresponde a um período definido em "Lotação"; ocupação/demora média/mortalidade recalculados só
                            com os episódios cuja saída ocorre dentro desse período. "Dias acima da lotação" = nº de dias desse período em
                            que o censo diário excedeu o nº de camas (doentes à espera de cama própria).
                        </Nota>
                    </Section>
                </div>

                {/* Saídas por dia da semana + urgentes vs programados */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Section title="Saídas por dia da semana" subtitle="Segunda a domingo.">
                        <div className="h-64">
                            <Bar
                                data={diaSemanaChart}
                                options={{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: commonScales,
                                }}
                            />
                        </div>
                        <Nota>contagem de episódios por dia da semana da data de saída.</Nota>
                    </Section>

                    <Section title="Urgentes vs. Programados" subtitle="Perfil de episódios por via de entrada (Urgência vs. restante).">
                        <div className="grid grid-cols-2 gap-4">
                            {(['urgentes', 'programados'] as const).map((chave) => {
                                const perfil = urgentesProgramados[chave];
                                return (
                                    <div key={chave} className="rounded-[6px] p-3" style={{ backgroundColor: CLINICAL_SOFT }}>
                                        <p className="text-xs font-semibold tracking-wide uppercase" style={{ color: MUTED }}>
                                            {chave === 'urgentes' ? 'Urgentes' : 'Programados'}
                                        </p>
                                        <p className="mt-1 text-xl font-semibold" style={{ color: INK }}>
                                            {fmt(perfil.episodios)}
                                            <span className="ml-1 text-sm font-normal" style={{ color: MUTED }}>
                                                ({fmt(perfil.pct)}%)
                                            </span>
                                        </p>
                                        <p className="mt-2 text-sm" style={{ color: INK }}>
                                            Demora média: <strong>{fmt(perfil.demoraMedia)} d</strong> (mediana {fmt(perfil.demoraMediana)})
                                        </p>
                                        <p className="mt-1 text-sm" style={{ color: ROSE }}>
                                            Mortalidade: {fmt(perfil.mortalidade)}%
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                        <Nota>
                            segmentação pela origem do episódio: "Urgência" vs. todas as restantes origens (consulta externa, cirurgia de
                            ambulatório, outro hospital, etc.), consideradas aqui como "programados".
                        </Nota>
                    </Section>
                </div>

                {/* Faixa etária */}
                <div>
                    <h2 className="mb-3 text-sm font-semibold tracking-wide uppercase" style={{ color: MUTED }}>
                        Idade
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <Section title="Episódios por faixa etária">
                            <div className="h-56">
                                <Bar
                                    data={faixaEtariaChart}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: commonScales,
                                    }}
                                />
                            </div>
                        </Section>
                        <Section title="Mortalidade por faixa etária" subtitle="% dos episódios de cada faixa.">
                            <div className="h-56">
                                <Bar
                                    data={mortalidadeEtariaChart}
                                    options={{
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: commonScales,
                                    }}
                                />
                            </div>
                        </Section>
                        <Section title="Altas com atraso por idade" subtitle="% que saem depois da alta clínica.">
                            <BarList rows={porFaixaEtaria} labelKey="faixa" valueKey="pctAltaComAtraso" accent={ROSE} suffix="%" />
                        </Section>
                    </div>
                    <Nota>
                        idade = anos entre a data de nascimento e a data de entrada do episódio (doentes com menos de 18 anos não entram
                        nestas faixas). Mortalidade e altas com atraso calculadas dentro de cada faixa, com as mesmas fórmulas da secção
                        "O período em números".
                    </Nota>
                </div>

                {/* Proveniência + Óbitos/readmissões */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Section title="Episódios por proveniência" subtitle="Origem do internamento, com demora média e mortalidade.">
                        <BarList rows={porProveniencia} labelKey="nome" valueKey="total" pctKey="pct" />
                        <Nota>agrupamento pela origem registada no episódio (Urgência, Consulta externa, Exterior, Outro hospital, etc.).</Nota>
                    </Section>

                    <Section title="Óbitos e readmissões">
                        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <KpiTile label="Óbitos" value={obitos.total} sub={`${fmt(obitos.pct)}% dos episódios`} tone="rose" />
                            <KpiTile label="Idade média dos óbitos" value={obitos.idadeMedia} suffix="anos" />
                            <KpiTile label="Óbitos em urgentes" value={obitos.pctUrgentes} suffix="%" />
                            <KpiTile label="Óbitos nos 1os 2 dias" value={obitos.pctPrimeiros2Dias} suffix="%" />
                            <KpiTile label="Readmissões a 30 dias" value={readmissoes.pct30} suffix="%" sub={`${fmt(readmissoes.total30)} episódios`} />
                            <KpiTile label="Readmissões pela Urgência" value={readmissoes.pctUrgencia} suffix="%" sub={`${fmt(readmissoes.totalUrgencia)} episódios`} />
                        </div>
                        <Nota>
                            óbitos = episódios marcados como "falecido". "Óbitos nos 1os 2 dias" = óbitos com ≤2 dias de internamento.
                            Readmissões = episódios do mesmo doente cuja entrada ocorre até 30 dias depois da saída anterior ("pela
                            Urgência" = subconjunto cuja origem da readmissão é Urgência).
                        </Nota>
                    </Section>
                </div>

                {/* Diagnósticos */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Section title="Grupos de diagnóstico mais frequentes" subtitle="Diagnóstico principal — top 10.">
                        <BarList rows={gruposDiagnostico} labelKey="nome" valueKey="total" pctKey="pct" />
                        <Nota>
                            diagnóstico principal de cada episódio, agrupado por "grupo de diagnóstico" (ou pelo nome do diagnóstico quando
                            sem grupo associado). % calculada sobre os episódios com diagnóstico principal registado.
                        </Nota>
                    </Section>

                    <Section title="Urgentes vs. Programados por grupo de diagnóstico" subtitle="Episódios e demora média — top 10 grupos.">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-left" style={{ borderColor: LINE, color: MUTED }}>
                                        <th className="py-1.5 pr-3 font-medium">Grupo de diagnóstico</th>
                                        <th className="py-1.5 pr-3 font-medium">Episódios</th>
                                        <th className="py-1.5 pr-3 font-medium">Urgentes</th>
                                        <th className="py-1.5 font-medium">Programados</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {diagnosticoUrgenteProgramado.map((d, i) => (
                                        <tr key={i} className="border-b" style={{ borderColor: LINE }}>
                                            <td className="py-1.5 pr-3 truncate" style={{ color: INK, maxWidth: 160 }} title={d.nome}>
                                                {d.nome}
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {fmt(d.total)} ({fmt(d.demoraMedia)} d)
                                            </td>
                                            <td className="py-1.5 pr-3" style={{ color: INK }}>
                                                {fmt(d.urgentes.episodios)} ({fmt(d.urgentes.demoraMedia)} d)
                                            </td>
                                            <td className="py-1.5" style={{ color: INK }}>
                                                {fmt(d.programados.episodios)} ({fmt(d.programados.demoraMedia)} d)
                                            </td>
                                        </tr>
                                    ))}
                                    {diagnosticoUrgenteProgramado.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="py-4 text-center" style={{ color: MUTED }}>
                                                Sem diagnósticos registados no período.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <Nota>
                            top 10 grupos de diagnóstico (acima), repartidos pela origem de cada episódio: "Urgentes" = origem Urgência,
                            "Programados" = restantes origens.
                        </Nota>
                    </Section>
                </div>

                {/* Síntese */}
                <Section title="Síntese sobre a eficiência" subtitle="Leitura automática dos indicadores acima — sem juízo clínico.">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p className="mb-2 text-xs font-semibold tracking-wide uppercase" style={{ color: CLINICAL }}>
                                Pontos a destacar
                            </p>
                            <ul className="list-disc space-y-1 pl-4 text-sm" style={{ color: INK }}>
                                <li>
                                    Ocupação de {fmt(anoNumeros.taxaOcupacao)}% com {fmt(anoNumeros.doentesPorCama)} doentes por cama e {fmt(anoNumeros.diaCamaVazia)} dia(s) de cama vazia entre doentes.
                                </li>
                                <li>
                                    Demora média de {fmt(anoNumeros.demoraMedia)} dias; programados com {fmt(urgentesProgramados.programados.demoraMedia)} dias vs. {fmt(urgentesProgramados.urgentes.demoraMedia)} dias nos urgentes.
                                </li>
                                <li>Mortalidade de {fmt(anoNumeros.mortalidade)}% ({fmt(urgentesProgramados.programados.mortalidade)}% nos programados).</li>
                                <li>{fmt(100 - anoNumeros.altasDepoisDaAltaClinica)}% das altas saem no dia da alta clínica.</li>
                            </ul>
                        </div>
                        <div>
                            <p className="mb-2 text-xs font-semibold tracking-wide uppercase" style={{ color: ROSE }}>
                                Pontos de atenção
                            </p>
                            <ul className="list-disc space-y-1 pl-4 text-sm" style={{ color: INK }}>
                                <li>
                                    Readmissões a 30 dias de {fmt(anoNumeros.readmissoes30dias)}% ({fmt(anoNumeros.readmissoesPelaUrgencia)}% pela Urgência).
                                </li>
                                <li>{fmt(anoNumeros.altasDepoisDaAltaClinica)}% das altas saem depois da alta clínica.</li>
                                <li>{fmt(readmissoes.totalSemana)} readmissões ocorrem na primeira semana após a alta.</li>
                                <li>Óbitos: idade média de {fmt(obitos.idadeMedia)} anos, {fmt(obitos.pctPrimeiros2Dias)}% nos primeiros 2 dias de internamento.</li>
                            </ul>
                        </div>
                    </div>
                </Section>

                <p className="text-xs" style={{ color: MUTED }}>
                    ULSRL | Sistema morbi 2.0 | Fonte: dados de internamento registados na aplicação.
                </p>
            </div>
        </AppLayout>
    );
}
