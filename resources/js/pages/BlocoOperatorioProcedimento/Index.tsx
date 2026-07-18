import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'BlocoOperatorioProcedimento',
        href: '/BlocoOperatorioProcedimento',
    },
];

interface BlocoOperatorioProcedimentoItem {
    id: number;
    [key: string]: any;
}

interface Props {
    items: BlocoOperatorioProcedimentoItem[];
}

export default function Index({ items }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="BlocoOperatorioProcedimento" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex justify-between items-center mb-4">
                    <h1 className="text-2xl font-semibold">BlocoOperatorioProcedimento</h1>
                    <Link
                        href={"/BlocoOperatorioProcedimento/create"}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg"
                    >
                        Criar Novo
                    </Link>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[50vh] flex-1 rounded-xl border overflow-hidden">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />

                    <table className="relative w-full border-collapse z-10">
                        <thead>
                            <tr>
              <th>bloco_operatorio_id</th>
              <th>descricao</th>
              <th>id</th>
              <th>procedimento_id</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((i) => (
                                <tr key={i.id}>
              <td>{i.bloco_operatorio_id}</td>
              <td>{i.descricao}</td>
              <td>{i.id}</td>
              <td>{i.procedimento_id}</td>
                                    <td>
                                        <Link
                                            href={"/BlocoOperatorioProcedimento/" + i.id + "/edit"}
                                            className="text-blue-600 underline"
                                        >
                                            Editar
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}