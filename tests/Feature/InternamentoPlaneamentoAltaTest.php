<?php

use App\Models\Internamento;
use App\Models\MotivoNaoAntecipacaoAlta;
use App\Models\User;

test('guarda data_prevista_alta, hora_alta e motivo_nao_antecipacao_alta_id', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create(['data_entrada' => '2026-01-01']);
    $motivo = MotivoNaoAntecipacaoAlta::factory()->create();

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'data_prevista_alta' => '2026-01-05',
        'hora_alta' => '14:30',
        'motivo_nao_antecipacao_alta_id' => $motivo->id,
    ])->assertSessionDoesntHaveErrors();

    $internamento->refresh();

    expect($internamento->data_prevista_alta)->toBe('2026-01-05');
    expect($internamento->motivo_nao_antecipacao_alta_id)->toBe($motivo->id);
});

test('rejeita data_prevista_alta anterior à data_entrada', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create(['data_entrada' => '2026-01-10']);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'data_prevista_alta' => '2026-01-01',
    ])->assertSessionHasErrors('data_prevista_alta');
});
