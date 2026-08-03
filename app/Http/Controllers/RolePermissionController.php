<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index()
    {
        return Inertia::render('RolesPermissions/Index', [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::query()->orderBy('name')->get(),
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::findOrCreate($data['name'], 'web');
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Role criada',
            'description' => 'A role foi criada com sucesso.',
        ]);
    }

    public function updateRole(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Role atualizada',
            'description' => 'As permissões da role foram atualizadas.',
        ]);
    }

    public function destroyRole(Role $role)
    {
        if ($role->name === 'admin') {
            return back()->withErrors(['role' => 'A role admin não pode ser removida.']);
        }

        $role->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Role removida',
            'description' => 'A role foi removida com sucesso.',
        ]);
    }

    public function storePermission(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::findOrCreate($data['name'], 'web');

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Permissão criada',
            'description' => 'A permissão foi criada com sucesso.',
        ]);
    }

    public function updatePermission(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update(['name' => $data['name']]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Permissão atualizada',
            'description' => 'A permissão foi atualizada com sucesso.',
        ]);
    }

    public function destroyPermission(Permission $permission)
    {
        $permission->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Permissão removida',
            'description' => 'A permissão foi removida com sucesso.',
        ]);
    }
}
