import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

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
    items: InternamentoItem[];
}

export default function Index({ items }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Internamento" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Internamento</h1>
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
                                        className={`hover:bg-neutral-50 dark:hover:bg-neutral-800 border-1 ${
                                            i.bloco_operatorios_count > 0 ? 'bg-blue-100' : 'bg-green-100'
                                        }`}
                                    >
                                        <td className="px-4 py-2">{i.patient.processo}</td>
                                        <td className="px-4 py-2">{i.data_entrada}</td>
                                        <td className="px-4 py-2">{i.data_saida}</td>
                                        <td className="px-4 py-2">{i.destino.nome}</td>
                                        <td className="px-4 py-2">{i.dias_internamento}</td>
                                        <td className="px-4 py-2">{i.clavien_dindo?.nome}</td>
                                        <td className="px-4 py-2">{i.responsavel?.name}</td>
                                        <td className="px-4 py-2">{i.observacoes}</td>
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
        </AppLayout>
    );
}
