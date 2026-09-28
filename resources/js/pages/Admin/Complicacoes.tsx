import { usePage, router, Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { useState } from 'react';
import toast from 'react-hot-toast';
import CreateOrUpdateModal from '@/components/complicacao/CreateOrUpdateModal';

export default function Complicacoes() {
    const { complicacoes, grupos } = usePage().props as any;

    const [openModal, setOpenModal] = useState(false);
    const [selected, setSelected] = useState(null);

    const openCreate = () => {
        setSelected(null);
        setOpenModal(true);
    };

    const openEdit = (complicacao: any) => {
        setSelected(complicacao);
        setOpenModal(true);
    };

    const destroy = (complicacao: any) => {
        if (!confirm(`Eliminar a complicação "${complicacao.nome}"?`)) return;

        router.delete(`/admin/complicacoes/${complicacao.id}`, {
            onSuccess: () => toast.success('Complicação eliminada com sucesso'),
            onError: () => toast.error('Erro ao eliminar a complicação'),
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: '/admin' }, { title: 'Complicações', href: '/admin/complicacoes' }]}>
            <Head title="Gestão de Complicações" />

            <div className="p-6 space-y-6">
                <div className="flex justify-between items-center">
                    <h1 className="text-xl font-semibold">Gestão de Complicações</h1>

                    <button
                        onClick={openCreate}
                        className="px-4 py-2 bg-green-600 text-white rounded"
                    >
                        Nova Complicação
                    </button>
                </div>

                <table className="w-full text-sm border rounded-lg">
                    <thead>
                        <tr className="border-b bg-neutral-100 dark:bg-neutral-800">
                            <th className="p-2 text-left">Nome</th>
                            <th className="p-2 text-left">Grupo</th>
                            <th className="p-2 text-left">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        {complicacoes.map((complicacao: any) => (
                            <tr key={complicacao.id} className="border-b">
                                <td className="p-2">{complicacao.nome}</td>
                                <td className="p-2">{complicacao.grupo_complicacao?.nome ?? '-'}</td>
                                <td className="p-2 flex gap-2">
                                    <button
                                        onClick={() => openEdit(complicacao)}
                                        className="px-3 py-1 rounded bg-neutral-700 text-white"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        onClick={() => destroy(complicacao)}
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
                complicacao={selected}
                grupos={grupos}
            />
        </AppLayout>
    );
}
