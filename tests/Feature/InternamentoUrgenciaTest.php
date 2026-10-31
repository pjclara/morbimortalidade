<?php

use App\Models\Internamento;
use App\Models\User;

test('dias_permanencia_urgencia calcula a diferença entre admissão na urgência e entrada', function () {
    $internamento = Internamento::factory()->create([
        'data_admissao_urgencia' => '2026-01-10',
        'data_entrada' => '2026-01-12',
    ]);

    expect($internamento->dias_permanencia_urgencia)->toBe(2);
});

test('dias_permanencia_urgencia é null sem data_admissao_urgencia', function () {
    $internamento = Internamento::factory()->create(['data_admissao_urgencia' => null]);

    expect($internamento->dias_permanencia_urgencia)->toBeNull();
});

test('guarda data_admissao_urgencia e episodio_urgencia via update', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create();

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'data_admissao_urgencia' => '2026-01-10',
        'episodio_urgencia' => 12345,
    ])->assertSessionDoesntHaveErrors();

    $internamento->refresh();

    expect($internamento->data_admissao_urgencia)->toBe('2026-01-10');
    expect($internamento->episodio_urgencia)->toBe(12345);
});
