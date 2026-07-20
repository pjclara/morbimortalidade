import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User',
        href: '/User',
    },
];

interface UserItem {
    id: number;
    [key: string]: any;
}

interface Props {
    items: UserItem[];
}

export default function Index({ items }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="User" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="mb-4 flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">User</h1>
                    <Link href={'/users/create'} className="rounded-lg bg-blue-600 px-4 py-2 text-white">
                        Criar Novo
                    </Link>
                </div>

                <div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[50vh] flex-1 overflow-hidden rounded-xl border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="relative z-10 overflow-x-auto">
                        <table className="w-full border-collapse text-sm">
                            <thead>
                                <tr className="bg-neutral-100 text-left dark:bg-neutral-800">
                                    <th className="px-4 py-3 font-semibold">name</th>
                                    <th className="px-4 py-3 font-semibold">username</th>
                                    <th className="px-4 py-3 font-semibold">ativo</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-200 dark:divide-neutral-700">
                                {items.data.map((i) => (
                                    <tr key={i.id}>
                                        <td className="px-4 py-2">{i.name}</td>
                                        <td className="px-4 py-2">{i.username}</td>
                                        <td className="px-4 py-2">{i.ativo}</td>

                                        <td className="px-4 py-2">
                                            <Link href={'/User/' + i.id + '/edit'} className="text-blue-600 underline">
                                                Editar
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {/* PAGINAÇÃO PREMIUM */}
                        <div className="mt-4 flex flex-col items-center gap-2">
                            {/* Informação de contagem */}
                            <div className="text-sm text-neutral-600 dark:text-neutral-300">
                                Mostrando {items.from}–{items.to} de {items.total} registos
                            </div>

                            {/* Paginação */}
                            <div className="inline-flex gap-1">
                                {items.links.map((link: any, index: number) => {
                                    const isActive = link.active;
                                    const isDisabled = link.url === null;

                                    return (
                                        <a
                                            key={index}
                                            href={link.url ?? undefined}
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
