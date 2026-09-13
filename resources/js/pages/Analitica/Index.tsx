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

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Analítica', href: '/analitica' }];

// Tokens partilhados com o módulo de Internamento — papel frio, verde-cirúrgico
// como único acento de dados de série única, e a dupla azul/laranja (validada
// para contraste e daltonismo) quando há duas séries a comparar lado a lado.
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

interface Kpis {
    totalInternamentos: number;
    totalCirurgias: number;
    mediaDiasInternamento: number;
    taxaMortalidade: number;
    taxaMortalidadeAposAlta: number;
    taxaAmbulatorio: number;
    taxaComplicacoes: number;
}

interface MesInternamento {
    mes: string;
    total: number;
    media_dias: number;
}

interface MesCirurgia {
    mes: string;
    total: number;
    ambulatorio: number;
    internamento: number;
}

interface NomeTotal {
    nome: string;
    total: number;
}

interface EquipaLinha extends NomeTotal {
    media_dias: number;
    obitos: number;
}

interface GrupoTotal {
    grupo: string;
    total: number;
}

interface FaixaTotal {
    faixa: string;
    total: number;
}

interface Equipa {
    id: number;
    nome: string;
}

interface Filtros {
    data_inicio?: string;
    data_fim?: string;
    equipa_id?: string;
}

interface PageProps {
    kpis: Kpis;
    internamentosPorMes: MesInternamento[];
    cirurgiasPorMes: MesCirurgia[];
    porEquipa: EquipaLinha[];
    porDestino: NomeTotal[];
    porOrigem: NomeTotal[];
    porClavienDindo: NomeTotal[];
    porSexo: NomeTotal[];
    porFaixaEtaria: FaixaTotal[];
    topDiagnosticos: NomeTotal[];
    porCapituloDiagnostico: NomeTotal[];
    porSeccaoDiagnostico: NomeTotal[];
    topComplicacoes: GrupoTotal[];
    porTipoCirurgia: NomeTotal[];
    porDiaSemana: { dia: string; total: number }[];
    topProcedimentos: NomeTotal[];
    porSistemaCorporal: NomeTotal[];
    porParteCorpo: NomeTotal[];
    equipas: Equipa[];
    filtros: Filtros;
    periodoPorOmissao: boolean;
}

function fmt(n: number) {
    return n.toLocaleString('pt-PT');
}

function KpiTile({ label, value, suffix, tone }: { label: string; value: number | string; suffix?: string; tone?: 'default' | 'rose' }) {
    return (
        <div className="rounded-[6px] border bg-white p-4" style={{ borderColor: LINE }}>
            <p className="text-xs font-medium" style={{ color: MUTED }}>
                {label}
            </p>
            <p className="mt-1.5 text-2xl font-semibold" style={{ color: tone === 'rose' ? ROSE : INK }}>
                {typeof value === 'number' ? fmt(value) : value}
                {suffix && <span className="ml-0.5 text-base font-medium" style={{ color: MUTED }}>{suffix}</span>}
            </p>
        </div>
    );
}

/**
 * Lista de barras horizontais — usada em vez de tartes/donuts para todas as
 * distribuições e rankings: uma única cor de acento (a identidade está no
 * rótulo, não na cor), extremidade arredondada a 4px, valor à direita da
 * barra (fora, nunca cortado).
 */
function BarList({
    rows,
    labelKey,
    valueKey,
    accent = CLINICAL,
    emptyText = 'Sem dados no período seleccionado.',
    limit,
}: {
    rows: Record<string, any>[];
    labelKey: string;
    valueKey: string;
    accent?: string;
    emptyText?: string;
    limit?: number;
}) {
    const data = limit ? rows.slice(0, limit) : rows;
    const max = Math.max(1, ...data.map((r) => Number(r[valueKey]) || 0));

    if (!data.length) {
        return <p className="text-sm" style={{ color: MUTED }}>{emptyText}</p>;
    }

    return (
        <div className="flex flex-col gap-2.5">
            {data.map((row, i) => {
                const value = Number(row[valueKey]) || 0;
                const pct = Math.max(2, (value / max) * 100);
                return (
                    <div key={i} className="grid grid-cols-[1fr_auto] items-center gap-3">
                        <div>
                            <p className="truncate text-sm" style={{ color: INK }} title={String(row[labelKey])}>
                                {row[labelKey] ?? '—'}
                            </p>
                            <div className="mt-1 h-1.5 w-full rounded-full" style={{ backgroundColor: CLINICAL_SOFT }}>
                                <div
                                    className="h-1.5 rounded-full transition-all"
                                    style={{ width: `${pct}%`, backgroundColor: accent }}
                                />
                            </div>
                        </div>
                        <span className="text-sm font-medium tabular-nums" style={{ color: INK }}>
                            {fmt(value)}
                        </span>
                    </div>
                );
            })}
        </div>
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

export default function AnaliticaIndex() {
    const {
        kpis,
        internamentosPorMes = [],
        cirurgiasPorMes = [],
        porEquipa = [],
        porDestino = [],
        porOrigem = [],
        porClavienDindo = [],
        porSexo = [],
        porFaixaEtaria = [],
        topDiagnosticos = [],
        porCapituloDiagnostico = [],
        porSeccaoDiagnostico = [],
        topComplicacoes = [],
        porTipoCirurgia = [],
        porDiaSemana = [],
        topProcedimentos = [],
        porSistemaCorporal = [],
        porParteCorpo = [],
        equipas = [],
        filtros,
        periodoPorOmissao,
    } = usePage<PageProps>().props;

    const [local, setLocal] = useState<Filtros>(filtros ?? {});

    function apply(next: Filtros) {
        setLocal(next);
        router.get('/analitica', next, { preserveState: true, preserveScroll: true });
    }

    function clear() {
        setLocal({});
        router.get('/analitica', {}, { preserveState: true, preserveScroll: true });
    }

    function verHistoricoCompleto() {
        const next = { ...local, data_inicio: undefined, data_fim: undefined };
        setLocal(next);
        router.get('/analitica', { ...next, todos: 1 }, { preserveState: true, preserveScroll: true });
    }

    const hasFiltros = Boolean(local.equipa_id) || !periodoPorOmissao;

    const lineChartData = {
        labels: internamentosPorMes.map((m) => m.mes),
        datasets: [
            {
                label: 'Internamentos',
                data: internamentosPorMes.map((m) => m.total),
                borderColor: CLINICAL,
                backgroundColor: 'rgba(30,111,92,0.10)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: CLINICAL,
                pointBorderColor: PAPER,
                pointBorderWidth: 2,
                fill: true,
                tension: 0.35,
            },
        ],
    };

    const barChartData = {
        labels: cirurgiasPorMes.map((m) => m.mes),
        datasets: [
            {
                label: 'Internamento',
                data: cirurgiasPorMes.map((m) => m.internamento),
                backgroundColor: SERIES_BLUE,
                borderRadius: 4,
                maxBarThickness: 22,
                stack: 'cirurgias',
            },
            {
                label: 'Ambulatório',
                data: cirurgiasPorMes.map((m) => m.ambulatorio),
                backgroundColor: SERIES_ORANGE,
                borderRadius: 4,
                maxBarThickness: 22,
                stack: 'cirurgias',
            },
        ],
    };

    const gridColor = LINE;
    const commonScales = {
        x: { grid: { display: false }, ticks: { color: MUTED, font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: MUTED, font: { size: 11 }, precision: 0 } },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Analítica" />

            <div className="flex flex-col gap-5 p-4 md:p-6" style={{ backgroundColor: PAPER }}>
                {/* Cabeçalho + filtros */}
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-lg font-semibold" style={{ color: INK }}>
                            Análise clínica
                        </h1>
                        <p className="text-sm" style={{ color: MUTED }}>
                            Internamentos e bloco operatório — visão agregada da base de dados.
                        </p>
                        {periodoPorOmissao && (
                            <p className="mt-1 text-xs" style={{ color: MUTED }}>
                                A mostrar os últimos 3 meses por questões de desempenho.{' '}
                                <button onClick={verHistoricoCompleto} className="underline underline-offset-2 hover:no-underline" style={{ color: CLINICAL }}>
                                    Ver histórico completo
                                </button>
                            </p>
                        )}
                    </div>

                    <div className="flex flex-wrap items-end gap-3">
                        <div className="flex flex-col gap-1">
                            <label className={fieldLabel}>Data início</label>
                            <input
                                type="date"
                                className={fieldInput}
                                value={local.data_inicio ?? ''}
                                onChange={(e) => apply({ ...local, data_inicio: e.target.value || undefined })}
                            />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className={fieldLabel}>Data fim</label>
                            <input
                                type="date"
                                className={fieldInput}
                                value={local.data_fim ?? ''}
                                onChange={(e) => apply({ ...local, data_fim: e.target.value || undefined })}
                            />
                        </div>
                        <div className="flex flex-col gap-1">
                            <label className={fieldLabel}>Equipa</label>
                            <select
                                className={fieldInput}
                                value={local.equipa_id ?? ''}
                                onChange={(e) => apply({ ...local, equipa_id: e.target.value || undefined })}
                            >
                                <option value="">Todas</option>
                                {equipas.map((eq) => (
                                    <option key={eq.id} value={eq.id}>
                                        {eq.nome}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {hasFiltros && (
                            <button
                                onClick={clear}
                                className="rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-neutral-50"
                                style={{ borderColor: '#C9D1CB', color: INK }}
                            >
                                Limpar filtros
                            </button>
                        )}
                    </div>
                </div>

                {/* KPIs */}
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
                    <KpiTile label="Internamentos" value={kpis.totalInternamentos} />
                    <KpiTile label="Cirurgias" value={kpis.totalCirurgias} />
                    <KpiTile label="Média dias internamento" value={kpis.mediaDiasInternamento} />
                    <KpiTile label="Ambulatório" value={kpis.taxaAmbulatorio} suffix="%" />
                    <KpiTile label="Complicações" value={kpis.taxaComplicacoes} suffix="%" />
                    <KpiTile label="Mortalidade" value={kpis.taxaMortalidade} suffix="%" tone="rose" />
                    <KpiTile label="Mortalidade após alta" value={kpis.taxaMortalidadeAposAlta} suffix="%" tone="rose" />
                </div>

                {/* Evolução temporal */}
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <Section title="Internamentos por mês" subtitle="Contagem de entradas, mês a mês.">
                        <div className="h-64">
                            <Line
                                data={lineChartData}
                                options={{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: false } },
                                    scales: commonScales,
                                }}
                            />
                        </div>
                    </Section>

                    <Section title="Cirurgias por mês" subtitle="Internamento vs. ambulatório.">
                        <div className="h-64">
                            <Bar
                                data={barChartData}
                                options={{
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            position: 'top',
                                            align: 'end',
                                            labels: { color: INK, boxWidth: 10, boxHeight: 10, font: { size: 12 } },
                                        },
                                    },
                                    scales: { ...commonScales, x: { ...commonScales.x, stacked: true }, y: { ...commonScales.y, stacked: true } },
                                }}
                            />
                        </div>
                    </Section>
                </div>

                {/* Internamentos — distribuições */}
                <div>
                    <h2 className="mb-3 text-sm font-semibold tracking-wide uppercase" style={{ color: MUTED }}>
                        Internamentos
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Section title="Por equipa" subtitle="Volume de internamentos por equipa responsável.">
                            <BarList rows={porEquipa} labelKey="nome" valueKey="total" />
                        </Section>

                        <Section title="Destino após alta">
                            <BarList rows={porDestino} labelKey="nome" valueKey="total" />
                        </Section>

                        <Section title="Origem do internamento">
                            <BarList rows={porOrigem} labelKey="nome" valueKey="total" />
                        </Section>

                        <Section title="Classificação Clavien-Dindo" subtitle="Gravidade das complicações associadas.">
                            <BarList rows={porClavienDindo} labelKey="nome" valueKey="total" accent={ROSE} />
                        </Section>

                        <Section title="Sexo">
                            <BarList rows={porSexo} labelKey="nome" valueKey="total" />
                        </Section>

                        <Section title="Faixa etária" subtitle="Idade à data de entrada.">
                            <BarList rows={porFaixaEtaria} labelKey="faixa" valueKey="total" />
                        </Section>

                        <Section title="Diagnósticos mais frequentes" subtitle="Top 10.">
                            <BarList rows={topDiagnosticos} labelKey="nome" valueKey="total" limit={10} />
                        </Section>

                        <Section title="Diagnósticos por capítulo CID-10" subtitle="Classificação internacional de doenças (ICD-10-CM).">
                            <BarList rows={porCapituloDiagnostico} labelKey="nome" valueKey="total" />
                        </Section>

                        <Section title="Diagnósticos por secção CID-10" subtitle="Top 10 secções mais frequentes.">
                            <BarList rows={porSeccaoDiagnostico} labelKey="nome" valueKey="total" limit={10} />
                        </Section>

                        <Section title="Complicações por grupo" subtitle="Agrupadas por categoria clínica.">
                            <BarList rows={topComplicacoes} labelKey="grupo" valueKey="total" accent={ROSE} />
                        </Section>
                    </div>
                </div>

                {/* Bloco operatório — distribuições */}
                <div>
                    <h2 className="mb-3 text-sm font-semibold tracking-wide uppercase" style={{ color: MUTED }}>
                        Bloco operatório
                    </h2>
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Section title="Tipos de cirurgia" subtitle="Top 10.">
                            <BarList rows={porTipoCirurgia} labelKey="nome" valueKey="total" limit={10} />
                        </Section>

                        <Section title="Cirurgias por dia da semana" subtitle="Segunda a domingo.">
                            <BarList rows={porDiaSemana} labelKey="dia" valueKey="total" />
                        </Section>

                        <Section title="Procedimentos mais frequentes" subtitle="Top 10.">
                            <BarList rows={topProcedimentos} labelKey="nome" valueKey="total" limit={10} />
                        </Section>

                        <Section title="Procedimentos por sistema corporal" subtitle="Classificação ICD-10-PCS (eixo 2).">
                            <BarList rows={porSistemaCorporal} labelKey="nome" valueKey="total" limit={10} />
                        </Section>

                        <Section title="Procedimentos por parte do corpo" subtitle="Classificação ICD-10-PCS (eixo 4) — top 10.">
                            <BarList rows={porParteCorpo} labelKey="nome" valueKey="total" limit={10} />
                        </Section>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
