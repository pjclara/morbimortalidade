<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Lista todos (ativos e inativos) para se poder reativar quem está desativado.
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

        $users = $query->paginate(20)->withQueryString();
        $roles = Role::all();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'estado', 'role']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'roles'    => 'array',
        ]);

        $user = User::create($data);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return $user->load('roles');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:users,email,{$user->id}",
            'password' => 'nullable|string|min:6',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Utilizador atualizado',
            'description' => 'O utilizador foi atualizado com sucesso.',
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }
}
