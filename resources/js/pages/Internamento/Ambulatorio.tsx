import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'react-hot-toast';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Internamento',
        href: '/internamentos',
    },
    {
        title: 'Ambulatório',
        href: '/internamento/ambulatorio',
    },
];

interface AmbulatorioItem {
    id: number;
    data_intervencao?: string;
    bloco_num?: string;
    internamento?: {
        patient?: { processo?: string; nome?: string };
    } | null;
    tipo_de_cirurgia?: { nome?: string } | null;
    bloco_operatorio_procedimentos?: { descricao?: string }[];
    [key: string]: any;
}

interface Props {
    items: {
        data: AmbulatorioItem[];
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters?: {
        processo?: string;
        data_de?: string;
        data_ate?: string;
        tipo_de_cirurgia_id?: number;
    };
    tipo_de_cirurgia_options: Record<string, number>;
}

// Mesmos tokens visuais do módulo de Internamento.
const INK = '#17241F';
const PAPER = '#F5F6F3';
const LINE = '#DCE1DC';
const CLINICAL = '#1E6F5C';

const fieldLabel = 'text-xs font-medium tracking-normal text-[#45524C]';
const fieldInput =
    'rounded-[4px] border border-[#C9D1CB] bg-white px-2.5 py-1.5 text-sm text-[#17241F] outline-none transition focus:border-[#1E6F5C] focus:ring-2 focus:ring-[#1E6F5C]/20 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100';

export default function Ambulatorio({ items, filters, tipo_de_cirurgia_options }: Props) {
    const currentFilters = filters ?? {};

    function applyFilter(newFilters: any) {
        router.get('/internamento/ambulatorio', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    }

    function handleFilterChange(key: string, value: any) {
        applyFilter({
            ...currentFilters,
            [key]: value || undefined,
        });
        toast.success('Filtros aplicados com sucesso!');
    }

    function clearFilters() {
        router.get(
            '/internamento/ambulatorio',
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
        toast.success('Filtros limpos com sucesso!');
    }

    const [loadingBloco, setLoadingBloco] = useState(false);

    function uploadExcelBloco(e: any) {
        const file = e.target.files?.[0];
        if (!file) return;

        const allowed = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];

        if (!allowed.includes(file.type)) {
            alert('Formato inválido. Use .xlsx ou .csv');
            return;
        }
        setLoadingBloco(true);

        const formData = new FormData();
        formData.append('file', file);

        router.post('/internamento/importBloco', formData, {
            onSuccess: (page) => {
                toast.success(`${page.props.imported} registos importados!`);

                (page.props.importErrors as string[] | undefined)?.forEach((err: string) => {
                    toast.error(err);
                });
            },
            onError: () => {
                toast.error('Erro ao importar ficheiro.');
            },
            onFinish: () => {
                setLoadingBloco(false);
            },
        });
    }

    const { auth } = usePage().props as any;
    const isSuperAdmin = auth.user?.roles?.includes('super-admin');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ambulatório">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap"
                    rel="stylesheet"
                />
            </Head>

            <div
                className="flex h-full flex-1 flex-col gap-5 p-4"
                style={{ fontFamily: "'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif" }}
            >
                {/* Cabeçalho */}
                <div className="flex items-end justify-between border-b pb-3" style={{ borderColor: LINE }}>
                    <div>
                        <h1 className="text-xl font-semibold" style={{ color: INK }}>
                            Ambulatório
                        </h1>
                        <p className="mt-0.5 text-sm text-[#5B685F]">
                            {items.total} {items.total === 1 ? 'doente operado' : 'doentes operados'} em ambulatório
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        {isSuperAdmin && (
                            <label
                                className="cursor-pointer rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#E3EFEA]"
                                style={{ borderColor: CLINICAL, color: CLINICAL }}
                            >
                                {loadingBloco ? 'A carregar…' : 'Importar blocos'}
                                <input type="file" accept=".xlsx,.csv" className="hidden" onChange={uploadExcelBloco} />
                            </label>
                        )}

                        <Link
                            href="/internamentos"
                            className="rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#E3EFEA]"
                            style={{ borderColor: CLINICAL, color: CLINICAL }}
                        >
                            ← Internamentos
                        </Link>
                    </div>
                </div>

                {/* Filtros */}
                <div className="rounded-[6px] border p-3" style={{ borderColor: LINE, backgroundColor: PAPER }}>
                    <div className="flex flex-wrap items-end gap-3">
                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Processo</span>
                            <input
                                className={fieldInput}
                                defaultValue={currentFilters.processo ?? ''}
                                onBlur={(e) => handleFilterChange('processo', e.target.value)}
                            />
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Intervenção (de)</span>
                            <input
                                type="date"
                                className={fieldInput}
                                defaultValue={currentFilters.data_de ?? ''}
                                onChange={(e) => handleFilterChange('data_de', e.target.value)}
                            />
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Intervenção (até)</span>
                            <input
                                type="date"
                                className={fieldInput}
                                defaultValue={currentFilters.data_ate ?? ''}
                                onChange={(e) => handleFilterChange('data_ate', e.target.value)}
                            />
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Tipo de cirurgia</span>
                            <select
                                className={fieldInput}
                                defaultValue={currentFilters.tipo_de_cirurgia_id ?? ''}
                                onChange={(e) => handleFilterChange('tipo_de_cirurgia_id', e.target.value)}
                            >
                                <option value="">Todos</option>
                                {Object.entries(tipo_de_cirurgia_options ?? {}).map(([nome, id]) => (
                                    <option key={id} value={id}>
                                        {nome}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <button
                            onClick={clearFilters}
                            className="px-1 py-1.5 text-sm font-medium underline decoration-[#C9D1CB] underline-offset-4 hover:text-[#17241F]"
                            style={{ color: '#5B685F' }}
                        >
                            Limpar filtros
                        </button>
                    </div>
                </div>

                {/* Tabela */}
                <div className="overflow-hidden rounded-[6px] border" style={{ borderColor: LINE }}>
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse text-sm">
                            <thead>
                                <tr className="text-left" style={{ backgroundColor: PAPER }}>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Processo
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Data intervenção
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Nº bloco
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Tipo de cirurgia
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Procedimentos
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.map((b: AmbulatorioItem) => (
                                    <tr
                                        key={b.id}
                                        className="border-t bg-white transition hover:bg-[#F5F6F3] dark:bg-neutral-950 dark:hover:bg-neutral-900"
                                        style={{ borderColor: LINE, boxShadow: `inset 3px 0 0 0 ${CLINICAL}` }}
                                    >
                                        <td
                                            className="px-4 py-2.5 tabular-nums"
                                            style={{ fontFamily: "'IBM Plex Mono', ui-monospace, monospace", color: INK }}
                                        >
                                            {b.internamento?.patient?.processo ?? '-'}
                                        </td>
                                        <td
                                            className="px-4 py-2.5 tabular-nums"
                                            style={{ fontFamily: "'IBM Plex Mono', ui-monospace, monospace" }}
                                        >
                                            {b.data_intervencao ?? '-'}
                                        </td>
                                        <td className="px-4 py-2.5">{b.bloco_num ?? '-'}</td>
                                        <td className="px-4 py-2.5">{b.tipo_de_cirurgia?.nome ?? '-'}</td>
                                        <td className="px-4 py-2.5">
                                            {(b.bloco_operatorio_procedimentos ?? []).map((p) => p.descricao).filter(Boolean).join(', ') || '-'}
                                        </td>
                                    </tr>
                                ))}

                                {items.data.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-6 text-center text-sm" style={{ color: '#5B685F' }}>
                                            Nenhum doente operado em ambulatório encontrado.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* Paginação */}
                        <div className="flex flex-col items-center gap-2 border-t px-4 py-3" style={{ borderColor: LINE }}>
                            <div className="text-sm" style={{ color: '#5B685F' }}>
                                A mostrar {items.from}–{items.to} de {items.total} registos
                            </div>

                            <div className="inline-flex gap-1">
                                {items.links.map((link: any, index: number) => {
                                    const isActive = link.active;
                                    const isDisabled = link.url === null;

                                    return (
                                        <a
                                            key={index}
                                            href={link.url ?? undefined}
                                            className={
                                                'rounded-[4px] px-3 py-1 text-sm transition-colors' +
                                                (isDisabled ? ' pointer-events-none opacity-40' : ' hover:bg-[#E3EFEA]')
                                            }
                                            style={isActive ? { backgroundColor: CLINICAL, color: '#fff' } : { color: '#45524C' }}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
