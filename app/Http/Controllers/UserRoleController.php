<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserRoleController extends Controller
{
    public function index()
    {
        // Mesmo componente que UserController::index(), por isso tem de
        // devolver "users" no mesmo formato paginado (users.data).
        return Inertia::render('Admin/Users', [
            'users' => User::with('roles')->orderBy('name')->paginate(20),
            'roles' => Role::all(),
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
