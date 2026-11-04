<?php

use App\Models\Internamento;
use App\Models\User;

test('demora_permanencia_urgencia é guardada diretamente, sem cálculo', function () {
    $internamento = Internamento::factory()->create([
        'demora_permanencia_urgencia' => 2,
    ]);

    expect($internamento->demora_permanencia_urgencia)->toBe(2);
});

test('demora_permanencia_urgencia pode ser null', function () {
    $internamento = Internamento::factory()->create(['demora_permanencia_urgencia' => null]);

    expect($internamento->demora_permanencia_urgencia)->toBeNull();
});

test('guarda demora_permanencia_urgencia via update', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create();

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'demora_permanencia_urgencia' => 2,
    ])->assertSessionDoesntHaveErrors();

    $internamento->refresh();

    expect($internamento->demora_permanencia_urgencia)->toBe(2);
});
