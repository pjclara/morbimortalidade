<?php 
namespace App\Services;

class MenuService
{
    public static function getMenuForUser($user)
    {
        // Se não houver utilizador autenticado → menu vazio
        if (!$user) {
            return [];
        }

        $menu = [
            [
                'title' => 'Painel de controlo',
                'href' => '/dashboard',
                'icon' => 'LayoutGrid',
                'roles' => ['super_admin', 'Interno da especialidade', 'Interno do geral', 'Visitante'] 
            ],
            [
                'title' => 'Internamentos',
                'href' => '/internamentos',
                'icon' => 'Folder',
                'roles' =>['super_admin', 'Interno da especialidade', 'Interno do geral'] 
            ],
            [
                'title' => 'Utilizadores',
                'href' => '/users',
                'icon' => 'Users',
                'roles' => ['super_admin'],
            ],
        ];

        return collect($menu)->filter(function ($item) use ($user) {
            if (isset($item['roles'])) {
                return $user->hasAnyRole($item['roles']);
            }

            if (isset($item['permissions'])) {
                return $user->hasAnyPermission($item['permissions']);
            }

            return true;
        })->values();
    }
}
