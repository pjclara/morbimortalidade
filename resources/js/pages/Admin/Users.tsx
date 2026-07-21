import { usePage, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Users() {
    const { users, roles } = usePage().props;

    const updateRoles = (userId: number, selectedRoles: string[]) => {
        router.post(`/admin/users/${userId}/roles`, {
            roles: selectedRoles,
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: '/admin' }, { title: 'Utilizadores', href: '/admin/users' }]}>
            <Head title="Gestão de Utilizadores" />

            <div className="p-6 space-y-6">

                <h1 className="text-xl font-semibold">Gestão de Roles</h1>

                <table className="w-full text-sm border rounded-lg">
                    <thead>
                        <tr className="border-b bg-neutral-100 dark:bg-neutral-800">
                            <th className="p-2 text-left">Nome</th>
                            <th className="p-2 text-left">Email</th>
                            <th className="p-2 text-left">Roles</th>
                        </tr>
                    </thead>

                    <tbody>
                        {users.map((user: any) => (
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
                            </tr>
                        ))}
                    </tbody>
                </table>

            </div>
        </AppLayout>
    );
}
