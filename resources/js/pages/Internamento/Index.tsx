import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'react-hot-toast';
import InternamentoModal from '../../components/internamento/InternamentoModal';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Internamento',
        href: '/Internamento',
    },
];

interface InternamentoItem {
    id: number;
    [key: string]: any;
}

interface Props {
    items: {
        data: InternamentoItem[];
        links: any[];
        from: number;
        to: number;
        total: number;
        meta?: {
            filters?: any;
        };
    };
    filters?: {
        processo?: string;
        data_entrada_de?: string;
        data_entrada_ate?: string;
        destino_id?: number;
        responsavel_id?: number | null;
        clavien_dindo_id?: number;
        falecido?: boolean;
        falecido_apos_alta?: boolean;
        bloco_operatorio?: '0' | '1';
    };
    responsavel_options: Record<string, number>;
}

// Tokens locais ao módulo de Internamento — um registo clínico denso, não um
// dashboard SaaS genérico. Verde-cirúrgico como único acento, papel frio,
// âmbar/vermelho reservados a avisos e desfechos críticos.
const INK = '#17241F';
const PAPER = '#F5F6F3';
const LINE = '#DCE1DC';
const CLINICAL = '#1E6F5C';
const CLINICAL_SOFT = '#E3EFEA';
const ROSE = '#A32F26';
const ROSE_SOFT = '#FBE7E4';

const fieldLabel = 'text-xs font-medium tracking-normal text-[#45524C]';
const fieldInput =
    'rounded-[4px] border border-[#C9D1CB] bg-white px-2.5 py-1.5 text-sm text-[#17241F] outline-none transition focus:border-[#1E6F5C] focus:ring-2 focus:ring-[#1E6F5C]/20 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100';

export default function Index({ items, filters, responsavel_options }: Props) {
    const [selected, setSelected] = useState<any>(null);
    const currentFilters = filters ?? {};

    function openModal(item: InternamentoItem) {
        setSelected(item);
    }

    function applyFilter(newFilters: any) {
        router.get('/internamentos', newFilters, {
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
            '/internamentos',
            {},
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
        toast.success('Filtros limpos com sucesso!');
    }

    const [loadingInternamento, setLoadingInternamento] = useState(false);
    const [loadingBloco, setLoadingBloco] = useState(false);

    // O modal já persiste as alterações (o seu próprio `save()` faz o PUT e o
    // Inertia recarrega os `items` da página com os dados reais do servidor).
    // Este callback serve só para manter o item selecionado sincronizado no
    // ecrã de detalhe enquanto essa recarga não chega — voltar a fazer PUT
    // aqui reenviava o mesmo pedido em cima do primeiro (ex: uma complicação
    // acabada de criar era recriada e a original, ainda sem o id novo no
    // estado local, acabava apagada pela lógica de sincronização do backend).
    function updateData(data: any) {
        if (!data?.id) {
            console.error('updateData called without id', data);
            toast.error('Não foi possível actualizar: identificador em falta.');
            return;
        }

        setSelected(data);
    }

    function uploadExcel(e: any) {
        const file = e.target.files?.[0];
        if (!file) return;

        const allowed = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];

        if (!allowed.includes(file.type)) {
            alert('Formato inválido. Use .xlsx ou .csv');
            return;
        }
        setLoadingInternamento(true);

        const formData = new FormData();
        formData.append('file', file);

        router.post('/internamento/import', formData, {
            onSuccess: (page) => {
                toast.success(`${page.props.imported} registos importados!`);

                (page.props.importErrors as string[] | undefined)?.forEach((err: string) => {
                    toast.error(err);
                });
            },
            onError: () => {
                toast.error('Erro ao importar ficheiro.');
            },
        });
        setLoadingInternamento(false);
    }

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
        });
        setLoadingBloco(false);
    }

    const { auth } = usePage().props as any;

    const isSuperAdmin = auth.user?.roles?.includes('super-admin');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Internamento">
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
                            Internamento
                        </h1>
                        <p className="mt-0.5 text-sm text-[#5B685F]">
                            {items.total} {items.total === 1 ? 'registo' : 'registos'} no período filtrado
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href="/internamento/ambulatorio"
                            className="rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#E3EFEA]"
                            style={{ borderColor: CLINICAL, color: CLINICAL }}
                        >
                            Ambulatório
                        </Link>

                            <div className="flex gap-2">
                                <label
                                    className="cursor-pointer rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#E3EFEA]"
                                    style={{ borderColor: CLINICAL, color: CLINICAL }}
                                >
                                    {loadingInternamento ? 'A carregar…' : 'Importar internamentos'}
                                    <input type="file" accept=".xlsx,.csv" className="hidden" onChange={uploadExcel} />
                                </label>

                                <label
                                    className="cursor-pointer rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#E3EFEA]"
                                    style={{ borderColor: CLINICAL, color: CLINICAL }}
                                >
                                    {loadingBloco ? 'A carregar…' : 'Importar blocos'}
                                    <input type="file" accept=".xlsx,.csv" className="hidden" onChange={uploadExcelBloco} />
                                </label>
                            </div>
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
                            <span className={fieldLabel}>Entrada (de)</span>
                            <input
                                type="date"
                                className={fieldInput}
                                defaultValue={currentFilters.data_entrada_de ?? ''}
                                onChange={(e) => handleFilterChange('data_entrada_de', e.target.value)}
                            />
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Entrada (até)</span>
                            <input
                                type="date"
                                className={fieldInput}
                                defaultValue={currentFilters.data_entrada_ate ?? ''}
                                onChange={(e) => handleFilterChange('data_entrada_ate', e.target.value)}
                            />
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Responsável</span>
                            <select
                                className={fieldInput}
                                defaultValue={currentFilters.responsavel_id ?? ''}
                                onChange={(e) => handleFilterChange('responsavel_id', e.target.value)}
                            >
                                <option value="">Todos</option>
                                {Object.entries(responsavel_options).map(([nome, id]) => (
                                    <option key={id} value={id}>
                                        {nome}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Falecido</span>
                            <select
                                className={fieldInput}
                                defaultValue={currentFilters.falecido === true ? '1' : currentFilters.falecido === false ? '0' : ''}
                                onChange={(e) => handleFilterChange('falecido', e.target.value)}
                            >
                                <option value="">Todos</option>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Faleceu após a alta</span>
                            <select
                                className={fieldInput}
                                defaultValue={
                                    currentFilters.falecido_apos_alta === true ? '1' : currentFilters.falecido_apos_alta === false ? '0' : ''
                                }
                                onChange={(e) => handleFilterChange('falecido_apos_alta', e.target.value)}
                            >
                                <option value="">Todos</option>
                                <option value="1">Sim</option>
                                <option value="0">Não</option>
                            </select>
                        </div>

                        <div className="flex flex-col gap-1">
                            <span className={fieldLabel}>Bloco operatório</span>
                            <select
                                className={fieldInput}
                                defaultValue={currentFilters.bloco_operatorio ?? ''}
                                onChange={(e) => handleFilterChange('bloco_operatorio', e.target.value)}
                            >
                                <option value="">Todos</option>
                                <option value="1">Com bloco</option>
                                <option value="0">Sem bloco</option>
                            </select>
                        </div>

                        <button onClick={clearFilters} className="px-1 py-1.5 text-sm font-medium underline decoration-[#C9D1CB] underline-offset-4 hover:text-[#17241F]" style={{ color: '#5B685F' }}>
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
                                        Entrada
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Saída
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Destino
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Diagnóstico principal
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Clavien-Dindo
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Responsável
                                    </th>
                                    <th className="px-4 py-2.5 font-medium" style={{ color: '#45524C' }}>
                                        Observações
                                    </th>
                                    <th className="px-4 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {items.data.map((i: any) => {
                                    const statusColor = i.falecido_apos_alta ? ROSE : i.bloco_operatorios_count > 0 ? CLINICAL : LINE;

                                    return (
                                        <tr
                                            key={i.id}
                                            className="border-t bg-white transition hover:bg-[#F5F6F3] dark:bg-neutral-950 dark:hover:bg-neutral-900"
                                            style={{ borderColor: LINE, boxShadow: `inset 3px 0 0 0 ${statusColor}` }}
                                        >
                                            <td
                                                className="px-4 py-2.5 tabular-nums"
                                                style={{ fontFamily: "'IBM Plex Mono', ui-monospace, monospace", color: INK }}
                                            >
                                                {i.patient?.processo ?? '-'}
                                            </td>
                                            <td className="px-4 py-2.5 tabular-nums" style={{ fontFamily: "'IBM Plex Mono', ui-monospace, monospace" }}>
                                                {i.data_entrada ?? '-'}
                                            </td>
                                            <td className="px-4 py-2.5 tabular-nums" style={{ fontFamily: "'IBM Plex Mono', ui-monospace, monospace" }}>
                                                {i.data_saida ?? '-'}
                                            </td>
                                            <td className="px-4 py-2.5">
                                                {i.destino?.nome ?? '-'}
                                                {i.falecido_apos_alta ? (
                                                    <span
                                                        className="ml-2 rounded-full px-2 py-0.5 text-xs font-medium"
                                                        style={{ backgroundColor: ROSE_SOFT, color: ROSE }}
                                                    >
                                                        Faleceu após a alta
                                                    </span>
                                                ) : null}
                                            </td>
                                            <td className="px-4 py-2.5">{i.diagnosticos?.find((d: any) => d.pivot?.principal)?.nome ?? '-'}</td>
                                            <td className="px-4 py-2.5">{i.clavien_dindo?.nome ?? '-'}</td>
                                            <td className="px-4 py-2.5">{i.responsavel?.name ?? '-'}</td>
                                            <td className="group relative px-4 py-2.5">
                                                {i.observacoes ? i.observacoes.slice(0, 10) + (i.observacoes.length > 10 ? '…' : '') : '-'}

                                                {i.observacoes && (
                                                    <span
                                                        className="absolute top-full left-0 z-50 mt-1 hidden max-w-xs rounded-[4px] px-2 py-1 text-xs text-white shadow-lg group-hover:block"
                                                        style={{ backgroundColor: INK }}
                                                    >
                                                        {i.observacoes}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-2.5 text-right">
                                                <button
                                                    onClick={() => openModal(i)}
                                                    className="rounded-[4px] border px-3 py-1 text-sm font-medium transition text-white cursor-pointer"
                                                    style={{ borderColor: CLINICAL, color: CLINICAL }}
                                                    onMouseEnter={(e) => (e.currentTarget.style.backgroundColor = CLINICAL)}
                                                    onMouseLeave={(e) => (e.currentTarget.style.backgroundColor = 'transparent')}
                                                >
                                                    Editar
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
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
                                            className={'rounded-[4px] px-3 py-1 text-sm transition-colors' + (isDisabled ? ' pointer-events-none opacity-40' : ' hover:bg-[#E3EFEA]')}
                                            style={
                                                isActive
                                                    ? { backgroundColor: CLINICAL, color: '#fff' }
                                                    : { color: '#45524C' }
                                            }
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <InternamentoModal
                open={!!selected}
                item={selected}
                onClose={() => setSelected(null)}
                onSave={(updated: any) => {
                    updateData(updated);
                }}
            />
        </AppLayout>
    );
}
