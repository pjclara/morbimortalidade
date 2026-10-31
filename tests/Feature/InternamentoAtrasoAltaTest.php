<?php

use App\Models\AtrasoAlta;
use App\Models\Internamento;
use App\Models\MotivoAtraso;
use App\Models\User;

test('dias_atraso_alta é null sem data_alta', function () {
    $internamento = Internamento::factory()->create(['data_alta' => null]);

    expect($internamento->dias_atraso_alta)->toBeNull();
});

test('dias_atraso_alta calcula a diferença quando data_saida é depois de data_alta', function () {
    $internamento = Internamento::factory()->create([
        'data_alta' => '2026-01-10',
        'data_saida' => '2026-01-15',
    ]);

    expect($internamento->dias_atraso_alta)->toBe(5);
});

test('dias_atraso_alta é null quando não há atraso', function () {
    $internamento = Internamento::factory()->create([
        'data_alta' => '2026-01-10',
        'data_saida' => '2026-01-10',
    ]);

    expect($internamento->dias_atraso_alta)->toBeNull();
});

test('update sincroniza atrasos_alta: cria, atualiza e remove linhas', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create([
        'data_alta' => '2026-01-10',
        'data_saida' => '2026-01-20',
    ]);
    $motivo1 = MotivoAtraso::factory()->create();
    $motivo2 = MotivoAtraso::factory()->create();

    $existente = AtrasoAlta::factory()->for($internamento, 'internamento')->create(['motivo_id' => $motivo1->id]);
    $paraRemover = AtrasoAlta::factory()->for($internamento, 'internamento')->create(['motivo_id' => $motivo1->id]);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'atrasos_alta' => [
            ['id' => $existente->id, 'motivo_id' => $motivo2->id, 'observacoes' => 'Atualizado'],
            ['motivo_id' => $motivo2->id, 'observacoes' => 'Nova linha'],
        ],
    ])->assertSessionDoesntHaveErrors();

    $internamento->refresh();

    expect($internamento->atrasosAlta)->toHaveCount(2);
    expect(AtrasoAlta::find($paraRemover->id))->toBeNull();
    expect(AtrasoAlta::find($existente->id)->motivo_id)->toBe($motivo2->id);
});

test('rejeita motivo_id inexistente em atrasos_alta', function () {
    $user = User::factory()->create();
    $internamento = Internamento::factory()->create();

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'atrasos_alta' => [
            ['motivo_id' => 999999],
        ],
    ])->assertSessionHasErrors('atrasos_alta.0.motivo_id');
});
