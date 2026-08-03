import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { FormEvent } from 'react';

export default function RolesPermissionsIndex() {
    const { roles, permissions } = usePage().props as {
        roles: Array<{ id: number; name: string; permissions: Array<{ id: number; name: string }> }>;
        permissions: Array<{ id: number; name: string }>;
    };

    const createRoleForm = useForm({
        name: '',
        permissions: [] as string[],
    });

    const createPermissionForm = useForm({
        name: '',
    });

    const togglePermission = (current: string[], permissionName: string) => {
        return current.includes(permissionName)
            ? current.filter((p) => p !== permissionName)
            : [...current, permissionName];
    };

    const submitCreateRole = (e: FormEvent) => {
        e.preventDefault();
        createRoleForm.post('/access-control/roles', {
            preserveScroll: true,
            onSuccess: () => createRoleForm.reset('name', 'permissions'),
        });
    };

    const submitCreatePermission = (e: FormEvent) => {
        e.preventDefault();
        createPermissionForm.post('/access-control/permissions', {
            preserveScroll: true,
            onSuccess: () => createPermissionForm.reset('name'),
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Roles & Permissões', href: '/access-control' }]}>
            <Head title="Gestão de Roles e Permissões" />

            <div className="space-y-6 p-6">
                <h1 className="text-2xl font-semibold">Gestão de Roles e Permissões</h1>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div className="rounded-lg border bg-white p-4">
                        <h2 className="mb-4 text-lg font-medium">Criar Role</h2>

                        <form onSubmit={submitCreateRole} className="space-y-4">
                            <div>
                                <Label>Nome da role</Label>
                                <Input
                                    value={createRoleForm.data.name}
                                    onChange={(e) => createRoleForm.setData('name', e.target.value)}
                                    placeholder="ex: gestor_agenda"
                                />
                            </div>

                            <div>
                                <Label className="mb-2 block">Permissões</Label>
                                <div className="max-h-48 space-y-2 overflow-y-auto rounded border p-2">
                                    {permissions.map((permission) => (
                                        <label key={permission.id} className="flex items-center gap-2 text-sm">
                                            <Checkbox
                                                checked={createRoleForm.data.permissions.includes(permission.name)}
                                                onCheckedChange={() =>
                                                    createRoleForm.setData(
                                                        'permissions',
                                                        togglePermission(createRoleForm.data.permissions, permission.name),
                                                    )
                                                }
                                            />
                                            <span>{permission.name}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <Button disabled={createRoleForm.processing}>Criar role</Button>
                        </form>
                    </div>

                    <div className="rounded-lg border bg-white p-4">
                        <h2 className="mb-4 text-lg font-medium">Criar Permissão</h2>

                        <form onSubmit={submitCreatePermission} className="space-y-4">
                            <div>
                                <Label>Nome da permissão</Label>
                                <Input
                                    value={createPermissionForm.data.name}
                                    onChange={(e) => createPermissionForm.setData('name', e.target.value)}
                                    placeholder="ex: agenda.approve"
                                />
                            </div>

                            <Button disabled={createPermissionForm.processing}>Criar permissão</Button>
                        </form>
                    </div>
                </div>

                <div className="rounded-lg border bg-white p-4">
                    <h2 className="mb-4 text-lg font-medium">Roles existentes</h2>

                    <div className="space-y-4">
                        {roles.map((role) => {
                            const currentPermissions = role.permissions.map((p) => p.name);

                            return (
                                <RoleRow
                                    key={role.id}
                                    role={role}
                                    permissions={permissions}
                                    currentPermissions={currentPermissions}
                                />
                            );
                        })}
                    </div>
                </div>

                <div className="rounded-lg border bg-white p-4">
                    <h2 className="mb-4 text-lg font-medium">Permissões existentes</h2>

                    <div className="space-y-2">
                        {permissions.map((permission) => (
                            <PermissionRow key={permission.id} permission={permission} />
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function RoleRow({
    role,
    permissions,
    currentPermissions,
}: {
    role: { id: number; name: string };
    permissions: Array<{ id: number; name: string }>;
    currentPermissions: string[];
}) {
    const form = useForm({
        name: role.name,
        permissions: currentPermissions,
    });

    const togglePermission = (permissionName: string) => {
        form.setData(
            'permissions',
            form.data.permissions.includes(permissionName)
                ? form.data.permissions.filter((p) => p !== permissionName)
                : [...form.data.permissions, permissionName],
        );
    };

    return (
        <div className="rounded border p-3">
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put(`/access-control/roles/${role.id}`, { preserveScroll: true });
                }}
                className="space-y-3"
            >
                <div className="flex items-center gap-2">
                    <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                    <Button type="submit" size="sm" disabled={form.processing}>
                        Guardar
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="destructive"
                        onClick={() => router.delete(`/access-control/roles/${role.id}`, { preserveScroll: true })}
                    >
                        Apagar
                    </Button>
                </div>

                <div className="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                    {permissions.map((permission) => (
                        <label key={permission.id} className="flex items-center gap-2 text-sm">
                            <Checkbox
                                checked={form.data.permissions.includes(permission.name)}
                                onCheckedChange={() => togglePermission(permission.name)}
                            />
                            <span>{permission.name}</span>
                        </label>
                    ))}
                </div>
            </form>
        </div>
    );
}

function PermissionRow({ permission }: { permission: { id: number; name: string } }) {
    const form = useForm({ name: permission.name });

    return (
        <div className="flex items-center gap-2 rounded border p-2">
            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
            <Button
                size="sm"
                onClick={() => form.put(`/access-control/permissions/${permission.id}`, { preserveScroll: true })}
                disabled={form.processing}
            >
                Guardar
            </Button>
            <Button
                size="sm"
                variant="destructive"
                onClick={() => router.delete(`/access-control/permissions/${permission.id}`, { preserveScroll: true })}
            >
                Apagar
            </Button>
        </div>
    );
}
