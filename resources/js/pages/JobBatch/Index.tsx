import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'JobBatch',
        href: '/JobBatch',
    },
];

interface JobBatchItem {
    id: number;
    [key: string]: any;
}

interface Props {
    items: JobBatchItem[];
}

export default function Index({ items }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="JobBatch" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex justify-between items-center mb-4">
                    <h1 className="text-2xl font-semibold">JobBatch</h1>
                    <Link
                        href={"/JobBatch/create"}
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
              <th>cancelled_at</th>
              <th>created_at</th>
              <th>failed_job_ids</th>
              <th>failed_jobs</th>
              <th>finished_at</th>
              <th>id</th>
              <th>name</th>
              <th>options</th>
              <th>pending_jobs</th>
              <th>total_jobs</th>
                            </tr>
                        </thead>
                        <tbody>
                            {items.map((i) => (
                                <tr key={i.id}>
              <td>{i.cancelled_at}</td>
              <td>{i.created_at}</td>
              <td>{i.failed_job_ids}</td>
              <td>{i.failed_jobs}</td>
              <td>{i.finished_at}</td>
              <td>{i.id}</td>
              <td>{i.name}</td>
              <td>{i.options}</td>
              <td>{i.pending_jobs}</td>
              <td>{i.total_jobs}</td>
                                    <td>
                                        <Link
                                            href={"/JobBatch/" + i.id + "/edit"}
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