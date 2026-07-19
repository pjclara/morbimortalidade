import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import InternamentoModal from '../../components/internamento/InternamentoModal';
import { toast } from 'react-hot-toast';

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
        meta?: {
            filters?: any;
        };
    };
    filters?: {
        processo?: string;
        data_entrada_de?: string;
        data_entrada_ate?: string;
        destino_id?: number;
        responsavel_id?: number;
        clavien_dindo_id?: number;
        falecido?: boolean;
    };
    destino_options: Record<string, number>;
    origem_options: Record<string, number>;
    responsavel_options: Record<string, number>;
    clavien_options: Record<string, number>;
    equipa_options: Record<string, number>;
}

export default function Index({ items, filters, destino_options, origem_options, responsavel_options, clavien_options, equipa_options }: Props) {
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

    function updateData(data: any) {
        items.data = items.data.map((item) => (item.id === data.id ? { ...item, ...data } : item));
        // Atualiza o estado do componente para refletir as alterações
        setSelected(null);
        setSelected((prev: any) => ({ ...prev, ...data }));
        router.put(`/internamentos/${data.id}`, data, {
            preserveState: true,
            preserveScroll: true,
        });
    }


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Internamento" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Internamento</h1>
                </div>

                {/* FILTROS AVANÇADOS */}
                <div className="mb-4 flex flex-wrap items-end gap-4">
                    {/* Processo */}
                    <div className="flex flex-col">
                        <span className="text-sm font-medium">Processo</span>
                        <input
                            className="rounded-md border px-2 py-1 dark:bg-neutral-900"
                            defaultValue={currentFilters.processo ?? ''}
                            onBlur={(e) => handleFilterChange('processo', e.target.value)}
                        />
                    </div>

                    {/* Data Entrada - De */}
                    <div className="flex flex-col">
                        <span className="text-sm font-medium">Entrada (de)</span>
                        <input
                            type="date"
                            className="rounded-md border px-2 py-1 dark:bg-neutral-900"
                            defaultValue={currentFilters.data_entrada_de ?? ''}
                            onChange={(e) => handleFilterChange('data_entrada_de', e.target.value)}
                        />
                    </div>

                    {/* Data Entrada - Até */}
                    <div className="flex flex-col">
                        <span className="text-sm font-medium">Entrada (até)</span>
                        <input
                            type="date"
                            className="rounded-md border px-2 py-1 dark:bg-neutral-900"
                            defaultValue={currentFilters.data_entrada_ate ?? ''}
                            onChange={(e) => handleFilterChange('data_entrada_ate', e.target.value)}
                        />
                    </div>

                    {/* Responsável */}
                    <div className="flex flex-col">
                        <span className="text-sm font-medium">Responsável</span>
                        <select
                            className="rounded-md border px-2 py-1 dark:bg-neutral-900"
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


                    {/* Falecido (boolean premium) */}
                    <div className="flex flex-col">
                        <span className="text-sm font-medium">Falecido</span>
                        <select
                            className="rounded-md border px-2 py-1 dark:bg-neutral-900"
                            defaultValue={currentFilters.falecido === true ? '1' : currentFilters.falecido === false ? '0' : ''}
                            onChange={(e) => handleFilterChange('falecido', e.target.value)}
                        >
                            <option value="">Todos</option>
                            <option value="1">Sim</option>
                            <option value="0">Não</option>
                        </select>
                    </div>

                    {/* Botão limpar filtros */}
                    <button onClick={clearFilters} className="rounded-md bg-neutral-200 px-3 py-1 text-sm dark:bg-neutral-800 dark:text-neutral-100">
                        Limpar filtros
                    </button>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />

                    <div className="relative z-10 overflow-x-auto">
                        <table className="w-full border-collapse text-sm">
                            <thead>
                                <tr className="bg-neutral-100 text-left dark:bg-neutral-800">
                                    <th className="px-4 py-3 font-semibold">Processo</th>
                                    <th className="px-4 py-3 font-semibold">Entrada</th>
                                    <th className="px-4 py-3 font-semibold">Saída</th>
                                    <th className="px-4 py-3 font-semibold">Destino</th>
                                    <th className="px-4 py-3 font-semibold">Dias de Internamento</th>
                                    <th className="px-4 py-3 font-semibold">Classificação Clavien-Dindo</th>
                                    <th className="px-4 py-3 font-semibold">Responsável</th>
                                    <th className="px-4 py-3 font-semibold">Observações</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                {items.data.map((i: any) => (
                                    <tr
                                        key={i.id}
                                        onDoubleClick={() => openModal(i)}
                                        className={`border-1 hover:bg-neutral-50 dark:hover:bg-neutral-800 ${
                                            i.bloco_operatorios_count > 0 ? 'bg-blue-100' : 'bg-green-100'
                                        }`}
                                    >
                                        <td className="px-4 py-2">{i.patient?.processo ?? '-'}</td>
                                        <td className="px-4 py-2">{i.data_entrada ?? '-'}</td>
                                        <td className="px-4 py-2">{i.data_saida ?? '-'}</td>
                                        <td className="px-4 py-2">{i.destino?.nome ?? '-'}</td>
                                        <td className="px-4 py-2">{i.dias_internamento ?? '-'}</td>
                                        <td className="px-4 py-2">{i.clavien_dindo?.nome ?? '-'}</td>
                                        <td className="px-4 py-2">{i.responsavel?.name ?? '-'}</td>
                                        <td className="px-4 py-2">{i.observacoes ?? '-'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* PAGINAÇÃO PREMIUM */}
                        <div className="mt-4 flex justify-center">
                            <div className="inline-flex gap-1">
                                {items.links.map((link: any, index: number) => {
                                    const isActive = link.active;
                                    const isDisabled = link.url === null;

                                    return (
                                        <a
                                            key={index}
                                            href={link.url ?? null}
                                            className={
                                                'rounded-md border px-3 py-1 text-sm transition-colors ' +
                                                (isActive
                                                    ? 'border-blue-600 bg-blue-600 text-white'
                                                    : 'border-neutral-300 bg-white text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300') +
                                                (isDisabled ? ' pointer-events-none opacity-50' : ' hover:bg-neutral-100 dark:hover:bg-neutral-800')
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
                    toast.success('Internamento atualizado com sucesso!');
                    updateData(updated);
                }}
            />
        </AppLayout>
    );
}
