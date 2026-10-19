import { usePage, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import CreateOrUpdateModal from '@/components/user/CreateOrUpdateModal';

export default function Users() {
    const { users, roles, filters } = usePage().props as any;

    const [openModal, setOpenModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

    const [search, setSearch] = useState(filters?.search ?? '');
    const [estado, setEstado] = useState(filters?.estado ?? '');
    const [role, setRole] = useState(filters?.role ?? '');

    const aplicarFiltros = (novosFiltros: Record<string, string>) => {
        router.get(
            window.location.pathname,
            { search, estado, role, ...novosFiltros },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const updateRoles = (userId: number, selectedRoles: string[]) => {
        router.post(`/admin/users/${userId}/roles`, {
            roles: selectedRoles,
        });
    };

    const toggleAtivo = (userId: number) => {
        router.post(`/admin/users/${userId}/toggle-ativo`, {}, { preserveScroll: true });
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

                <div className="flex flex-wrap items-end gap-3 rounded-lg border bg-neutral-50 p-4 dark:bg-neutral-800">
                    <div>
                        <label className="block text-xs text-gray-500">Procurar</label>
                        <input
                            type="text"
                            placeholder="Nome ou email"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && aplicarFiltros({ search })}
                            className="mt-1 rounded border-gray-300 p-2"
                        />
                    </div>

                    <div>
                        <label className="block text-xs text-gray-500">Estado</label>
                        <select
                            value={estado}
                            onChange={(e) => {
                                setEstado(e.target.value);
                                aplicarFiltros({ estado: e.target.value });
                            }}
                            className="mt-1 rounded border-gray-300 p-2"
                        >
                            <option value="">Todos</option>
                            <option value="ativo">Ativos</option>
                            <option value="inativo">Inativos</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs text-gray-500">Perfil</label>
                        <select
                            value={role}
                            onChange={(e) => {
                                setRole(e.target.value);
                                aplicarFiltros({ role: e.target.value });
                            }}
                            className="mt-1 rounded border-gray-300 p-2"
                        >
                            <option value="">Todos</option>
                            {roles.map((r: any) => (
                                <option key={r.id} value={r.name}>
                                    {r.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <button
                        onClick={() => aplicarFiltros({ search })}
                        className="rounded bg-neutral-700 px-4 py-2 text-white"
                    >
                        Filtrar
                    </button>
                </div>

                <table className="w-full text-sm border rounded-lg">
                    <thead>
                        <tr className="border-b bg-neutral-100 dark:bg-neutral-800">
                            <th className="p-2 text-left">Nome</th>
                            <th className="p-2 text-left">Email</th>
                            <th className="p-2 text-left">Estado</th>
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
                                    <button
                                        role="switch"
                                        aria-checked={user.ativo}
                                        title={user.ativo ? 'Clicar para desativar' : 'Clicar para ativar'}
                                        onClick={() => toggleAtivo(user.id)}
                                        className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                            user.ativo ? 'bg-green-600' : 'bg-neutral-300 dark:bg-neutral-600'
                                        }`}
                                    >
                                        <span
                                            className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                                user.ativo ? 'translate-x-6' : 'translate-x-1'
                                            }`}
                                        />
                                    </button>
                                </td>

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

                <div className="flex items-center justify-between text-sm text-gray-600">
                    <span>
                        A mostrar {users.from ?? 0}–{users.to ?? 0} de {users.total ?? 0} utilizadores
                    </span>

                    <div className="inline-flex gap-1">
                        {(users.links ?? []).map((link: any, index: number) => {
                            const isDisabled = link.url === null;

                            return (
                                <button
                                    key={index}
                                    disabled={isDisabled}
                                    onClick={() => link.url && router.get(link.url, {}, { preserveState: true, preserveScroll: true })}
                                    className={`rounded px-3 py-1 ${
                                        link.active
                                            ? 'bg-neutral-700 text-white'
                                            : isDisabled
                                              ? 'cursor-not-allowed opacity-40'
                                              : 'border hover:bg-neutral-100 dark:hover:bg-neutral-800'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </div>
                </div>

            </div>

            <CreateOrUpdateModal
                open={openModal}
                onClose={() => setOpenModal(false)}
                user={selectedUser}
            />
        </AppLayout>
    );
}
