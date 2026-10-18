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
        return Inertia::render('Admin/Users', [
            'users' => User::with('roles')->get(),
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
