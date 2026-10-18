<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        // Mesmo componente que UserController::index(), por isso tem de
        // devolver "users" no mesmo formato paginado (users.data) e aceitar
        // os mesmos filtros.
        $query = User::with('roles')->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('ativo', $request->estado === 'ativo' ? 1 : 0);
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        return Inertia::render('Admin/Users', [
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'estado', 'role']),
        ]);
    }

    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'array',
        ]);

        $user->syncRoles($request->roles);

        return back()->with('success', 'Roles atualizados.');
    }

    public function toggleAtivo(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['ativo' => 'Não podes desativar a tua própria conta.']);
        }

        $user->update(['ativo' => !$user->ativo]);

        return back()->with('success', $user->ativo ? 'Utilizador ativado.' : 'Utilizador desativado.');
    }
}
