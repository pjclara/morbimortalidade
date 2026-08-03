import { usePage, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CreateOrUpdateModal from '@/components/user/CreateOrUpdateModal';

export default function Users() {
    const { users, roles } = usePage().props;

    const [openModal, setOpenModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

    const updateRoles = (userId: number, selectedRoles: string[]) => {
        router.post(`/admin/users/${userId}/roles`, {
            roles: selectedRoles,
        });
    };

    const openCreate = () => {
        setSelectedUser(null);
        setOpenModal(true);
    };

    const openEdit = (user: any) => {
        setSelectedUser(user);
        setOpenModal(true);
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: '/admin' }, { title: 'Utilizadores', href: '/admin/users' }]}>
            <Head title="Gestão de Utilizadores" />

            <div className="p-6 space-y-6">

                <div className="flex justify-between items-center">
                    <h1 className="text-xl font-semibold">Gestão de Utilizadores</h1>

                    <button
                        onClick={openCreate}
                        className="px-4 py-2 bg-green-600 text-white rounded"
                    >
                        Novo Utilizador
                    </button>
                </div>

                <table className="w-full text-sm border rounded-lg">
                    <thead>
                        <tr className="border-b bg-neutral-100 dark:bg-neutral-800">
                            <th className="p-2 text-left">Nome</th>
                            <th className="p-2 text-left">Email</th>
                            <th className="p-2 text-left">Roles</th>
                            <th className="p-2 text-left">Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        {users.data.map((user: any) => (
                            <tr key={user.id} className="border-b">
                                <td className="p-2">{user.name}</td>
                                <td className="p-2">{user.email}</td>

                                <td className="p-2">
                                    <div className="flex gap-2 flex-wrap">
                                        {roles.map((role: any) => {
                                            const active = user.roles.some((r: any) => r.name === role.name);

                                            return (
                                                <button
                                                    key={role.id}
                                                    onClick={() => {
                                                        const newRoles = active
                                                            ? user.roles.filter((r: any) => r.name !== role.name).map((r: any) => r.name)
                                                            : [...user.roles.map((r: any) => r.name), role.name];

                                                        updateRoles(user.id, newRoles);
                                                    }}
                                                    className={`px-3 py-1 rounded border ${
                                                        active
                                                            ? 'bg-blue-600 text-white border-blue-600'
                                                            : 'bg-white dark:bg-neutral-900 border-neutral-400'
                                                    }`}
                                                >
                                                    {role.name}
                                                </button>
                                            );
                                        })}
                                    </div>
                                </td>

                                <td className="p-2">
                                    <button
                                        onClick={() => openEdit(user)}
                                        className="px-3 py-1 rounded bg-neutral-700 text-white"
                                    >
                                        Editar
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
                user={selectedUser}
            />
        </AppLayout>
    );
}
