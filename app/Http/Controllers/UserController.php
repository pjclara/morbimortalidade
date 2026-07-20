<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::orderBy('name');

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->ativo);
        }else{
            $query->where('ativo', 1);
        }
        return Inertia::render('Users/Index', [
            'items' => $query->paginate(10)->withQueryString(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create');
    }

    public function store(UserStoreRequest $request)
    {
        User::create($request->validated());
        return redirect()->route('users.index')->with('success', 'Utilizador criado.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Users/Edit', [
            'item' => $user,
        ]);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $user->update($request->validated());
        return redirect()->route('users.index')->with('success', 'Utilizador atualizado.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Utilizador removido.');
    }
}
