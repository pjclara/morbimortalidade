<?php

use App\Models\Internamento;
use App\Models\User;

test('guarda classificação de doente complexo', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create();

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'asa' => 3,
        'indice_charlson' => 5,
        'dias_cuidados_intermedios' => 2,
        'dias_cuidados_intensivos' => 1,
    ])->assertSessionDoesntHaveErrors();

    $internamento->refresh();

    expect($internamento->asa)->toBe(3);
    expect($internamento->indice_charlson)->toBe(5);
    expect($internamento->dias_cuidados_intermedios)->toBe(2);
    expect($internamento->dias_cuidados_intensivos)->toBe(1);
});

test('rejeita asa fora do intervalo 1-5', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create();

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'asa' => 6,
    ])->assertSessionHasErrors('asa');
});
