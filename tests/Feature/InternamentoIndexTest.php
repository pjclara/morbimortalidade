<?php

use App\Models\Internamento;
use App\Models\User;

test('a listagem de internamentos carrega sem erros com os novos relacionamentos', function () {
    $user = User::factory()->create();
    Internamento::factory()->create();

    $this->actingAs($user)->get('/internamentos')->assertOk();
});
