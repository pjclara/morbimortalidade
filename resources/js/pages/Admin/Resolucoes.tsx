import { usePage, router, Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useState } from 'react';
import toast from 'react-hot-toast';
import CreateOrUpdateModal from '@/components/resolucao/CreateOrUpdateModal';

export default function Resolucoes() {
    const { resolucoes, clavienDindos } = usePage().props as any;

    const [openModal, setOpenModal] = useState(false);
    const [selected, setSelected] = useState(null);

    const openCreate = () => {
        setSelected(null);
        setOpenModal(true);
    };

    const openEdit = (resolucao: any) => {
        setSelected(resolucao);
        setOpenModal(true);
    };

    const destroy = (resolucao: any) => {
        if (!confirm(`Eliminar a resolução "${resolucao.nome}"?`)) return;

        router.delete(`/admin/resolucoes/${resolucao.id}`, {
            onSuccess: () => toast.success('Resolução eliminada com sucesso'),
            onError: () => toast.error('Erro ao eliminar a resolução'),
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: '/admin' }, { title: 'Resoluções', href: '/admin/resolucoes' }]}>
            <Head title="Gestão de Resoluções" />

            <div className="p-6 space-y-6">
                <div className="flex justify-between items-center">
                    <h1 className="text-xl font-semibold">Gestão de Resoluções</h1>

                    <button
                        onClick={openCreate}
                        className="px-4 py-2 bg-green-600 text-white rounded"
                    >
                        Nova Resolução
                    </button>
                </div>

                <table className="w-full text-sm border rounded-lg">
                    <thead>
                        <tr className="border-b bg-neutral-100 dark:bg-neutral-800">
                            <th className="p-2 text-left">Nome</th>
                            <th className="p-2 text-left">Descrição</th>
                            <th className="p-2 text-left">Clavien-Dindo</th>
                            <th className="p-2 text-left">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        {resolucoes.map((resolucao: any) => (
                            <tr key={resolucao.id} className="border-b">
                                <td className="p-2">{resolucao.nome}</td>
                                <td className="p-2">{resolucao.descricao ?? '-'}</td>
                                <td className="p-2">{resolucao.clavien_dindo?.nome ?? '-'}</td>
                                <td className="p-2 flex gap-2">
                                    <button
                                        onClick={() => openEdit(resolucao)}
                                        className="px-3 py-1 rounded bg-neutral-700 text-white"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        onClick={() => destroy(resolucao)}
                                        className="px-3 py-1 rounded bg-red-600 text-white"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <CreateOrUpdateModal
                open={openModal}
                onClose={() => setOpenModal(false)}
                resolucao={selected}
                clavienDindos={clavienDindos}
            />
        </AppLayout>
    );
}
