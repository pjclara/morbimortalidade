import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ComplicacaoResolucao',
        href: '/ComplicacaoResolucao',
    },
];

interface ComplicacaoResolucaoItem {
    id: number;
    [key: string]: any;
}

interface Props {
    items: ComplicacaoResolucaoItem[];
}

export default function Index({ items }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ComplicacaoResolucao" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex justify-between items-center mb-4">
                    <h1 className="text-2xl font-semibold">ComplicacaoResolucao</h1>
                    <Link
                        href={"/ComplicacaoResolucao/create"}
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
              <th>complicacao_internamento_id</th>
              <th>created_at</th>
              <th>id</th>
              <th>resolucao_id</th>
              <th>updated_at</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((i) => (
                                <tr key={i.id}>
              <td>{i.complicacao_internamento_id}</td>
              <td>{i.created_at}</td>
              <td>{i.id}</td>
              <td>{i.resolucao_id}</td>
              <td>{i.updated_at}</td>
                                    <td>
                                        <Link
                                            href={"/ComplicacaoResolucao/" + i.id + "/edit"}
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