import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Indicadores', href: '/indicadores' }];

const CLINICAL = '#1E6F5C';
const CLINICAL_SOFT = '#E3EFEA';
const MUTED = '#6B776F';

const fieldLabel = 'text-xs font-medium tracking-normal text-[#45524C]';
const fieldInput =
    'rounded-[4px] border border-[#C9D1CB] bg-white px-2.5 py-1.5 text-sm text-[#17241F] outline-none transition focus:border-[#1E6F5C] focus:ring-2 focus:ring-[#1E6F5C]/20 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100';

const DIAS_SEMANA = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

interface Filtros {
    data_inicio: string;
    data_fim: string;
    equipa_id: number | null;
}

interface AltasPorDiaSemana {
    total: number;
    por_dia_semana: number[];
    percentagem_sabado: number;
    percentagem_domingo: number;
    percentagem_fim_de_semana: number;
}

interface DemoraMedia {
    media_total: number;
    media_programados: number;
    media_urgentes: number;
    estadias_longas_n: number;
    estadias_longas_percentagem: number;
}

interface AtrasoAltaClinica {
    total_dias_atraso: number;
    doentes_com_atraso: number;
    doentes_7_ou_mais_dias: number;
    por_motivo: Record<string, number>;
}

interface Readmissoes30Dias {
    total_internamentos: number;
    n_readmissoes: number;
    percentagem: number;
    percentagem_primeira_semana: number;
    planeadas: number;
    nao_planeadas: number;
    relacionadas: number;
    nao_relacionadas: number;
}

interface OcupacaoDiaria {
    dias_acima_lotacao: number;
    entradas_programadas_por_dia_semana: number[];
    dia_semana_mais_entradas_programadas: number;
}

interface Props {
    filtros: Filtros;
    equipa_options: Record<string, number>;
    altas_por_dia_semana: AltasPorDiaSemana;
    demora_media: DemoraMedia;
    atraso_alta_clinica: AtrasoAltaClinica;
    readmissoes_30_dias: Readmissoes30Dias;
    ocupacao_diaria: OcupacaoDiaria;
}

function Kpi({ label, value, hint }: { label: string; value: string | number; hint?: string }) {
    return (
        <div className="rounded-[6px] border border-[#DCE1DC] bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900">
            <div className={fieldLabel}>{label}</div>
            <div className="mt-1 text-2xl font-semibold" style={{ color: CLINICAL }}>
                {value}
            </div>
            {hint ? <div className="mt-1 text-xs" style={{ color: MUTED }}>{hint}</div> : null}
        </div>
    );
}

export default function Index({
    filtros,
    equipa_options,
    altas_por_dia_semana,
    demora_media,
    atraso_alta_clinica,
    readmissoes_30_dias,
    ocupacao_diaria,
}: Props) {
    function atualizarFiltro(campo: string, valor: string) {
        router.get('/indicadores', { ...filtros, [campo]: valor || undefined }, { preserveState: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Indicadores" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex flex-wrap items-end gap-3">
                    <div className="flex flex-col gap-1">
                        <label className={fieldLabel}>De</label>
                        <input
                            type="date"
                            className={fieldInput}
                            defaultValue={filtros.data_inicio}
                            onChange={(e) => atualizarFiltro('data_inicio', e.target.value)}
                        />
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className={fieldLabel}>Até</label>
                        <input
                            type="date"
                            className={fieldInput}
                            defaultValue={filtros.data_fim}
                            onChange={(e) => atualizarFiltro('data_fim', e.target.value)}
                        />
                    </div>
                    <div className="flex flex-col gap-1">
                        <label className={fieldLabel}>Equipa</label>
                        <select
                            className={fieldInput}
                            defaultValue={filtros.equipa_id ?? ''}
                            onChange={(e) => atualizarFiltro('equipa_id', e.target.value)}
                        >
                            <option value="">Todas</option>
                            {Object.entries(equipa_options).map(([nome, id]) => (
                                <option key={id} value={id}>
                                    {nome}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <section>
                    <h2 className="mb-2 text-sm font-semibold" style={{ color: CLINICAL }}>
                        Altas ao fim de semana
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <Kpi label="Total de altas" value={altas_por_dia_semana.total} />
                        <Kpi label="% ao sábado" value={`${altas_por_dia_semana.percentagem_sabado}%`} />
                        <Kpi label="% ao domingo" value={`${altas_por_dia_semana.percentagem_domingo}%`} />
                        <Kpi label="% fim de semana" value={`${altas_por_dia_semana.percentagem_fim_de_semana}%`} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-2 text-sm font-semibold" style={{ color: CLINICAL }}>
                        Demora média e estadias longas
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                        <Kpi label="Média total" value={`${demora_media.media_total} dias`} />
                        <Kpi label="Média programados" value={`${demora_media.media_programados} dias`} />
                        <Kpi label="Média urgentes" value={`${demora_media.media_urgentes} dias`} />
                        <Kpi label="Estadias ≥15 dias" value={demora_media.estadias_longas_n} />
                        <Kpi label="% estadias longas" value={`${demora_media.estadias_longas_percentagem}%`} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-2 text-sm font-semibold" style={{ color: CLINICAL }}>
                        Atraso após alta clínica
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <Kpi label="Dias totais de atraso" value={atraso_alta_clinica.total_dias_atraso} />
                        <Kpi label="Doentes com atraso" value={atraso_alta_clinica.doentes_com_atraso} />
                        <Kpi label="Doentes com ≥7 dias" value={atraso_alta_clinica.doentes_7_ou_mais_dias} />
                    </div>
                    {Object.keys(atraso_alta_clinica.por_motivo).length > 0 && (
                        <table className="mt-3 w-full text-sm">
                            <thead>
                                <tr className="text-left" style={{ color: MUTED }}>
                                    <th className="py-1">Motivo</th>
                                    <th className="py-1 text-right">Nº</th>
                                </tr>
                            </thead>
                            <tbody>
                                {Object.entries(atraso_alta_clinica.por_motivo).map(([motivo, n]) => (
                                    <tr key={motivo} className="border-t" style={{ borderColor: CLINICAL_SOFT }}>
                                        <td className="py-1">{motivo}</td>
                                        <td className="py-1 text-right">{n}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </section>

                <section>
                    <h2 className="mb-2 text-sm font-semibold" style={{ color: CLINICAL }}>
                        Readmissões a 30 dias
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-6">
                        <Kpi label="Internamentos" value={readmissoes_30_dias.total_internamentos} />
                        <Kpi label="Readmissões" value={readmissoes_30_dias.n_readmissoes} />
                        <Kpi label="% readmissão" value={`${readmissoes_30_dias.percentagem}%`} />
                        <Kpi label="% na 1ª semana" value={`${readmissoes_30_dias.percentagem_primeira_semana}%`} />
                        <Kpi label="Planeadas / não planeadas" value={`${readmissoes_30_dias.planeadas} / ${readmissoes_30_dias.nao_planeadas}`} />
                        <Kpi label="Relacionadas / não relacionadas" value={`${readmissoes_30_dias.relacionadas} / ${readmissoes_30_dias.nao_relacionadas}`} />
                    </div>
                </section>

                <section>
                    <h2 className="mb-2 text-sm font-semibold" style={{ color: CLINICAL }}>
                        Margem de camas
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <Kpi label="Dias acima da lotação" value={ocupacao_diaria.dias_acima_lotacao} />
                        <Kpi
                            label="Dia com mais entradas programadas"
                            value={DIAS_SEMANA[ocupacao_diaria.dia_semana_mais_entradas_programadas] ?? '-'}
                        />
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}
