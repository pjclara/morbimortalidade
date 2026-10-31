<?php

use App\Models\Destino;
use App\Models\Internamento;
use App\Models\Patient;
use App\Models\User;

test('scopeReadmissaoA30Dias identifica internamento com alta anterior há 10 dias', function () {
    $patient = Patient::factory()->create();

    $anterior = Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-01',
        'data_saida' => '2026-01-05',
    ]);

    $atual = Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-15',
        'data_saida' => '2026-01-20',
    ]);

    $resultado = Internamento::readmissaoA30Dias()->pluck('id');

    expect($resultado)->toContain($atual->id);
    expect($resultado)->not->toContain($anterior->id);
});

test('scopeReadmissaoA30Dias não identifica altas com mais de 30 dias de intervalo', function () {
    $patient = Patient::factory()->create();

    Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-01',
        'data_saida' => '2026-01-05',
    ]);

    $atual = Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-03-01',
        'data_saida' => '2026-03-05',
    ]);

    $resultado = Internamento::readmissaoA30Dias()->pluck('id');

    expect($resultado)->not->toContain($atual->id);
});

test('internamentoIndice devolve o internamento anterior mais recente', function () {
    $patient = Patient::factory()->create();

    $maisAntigo = Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-01',
        'data_saida' => '2026-01-03',
    ]);

    $maisRecente = Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-10',
        'data_saida' => '2026-01-12',
    ]);

    $atual = Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-20',
        'data_saida' => '2026-01-25',
    ]);

    expect($atual->internamentoIndice()->id)->toBe($maisRecente->id);
});

test('rejeita data_consulta_pos_alta anterior à data_saida', function () {
    $user = User::factory()->create();
    $destino = Destino::factory()->create(['nome' => 'Domicílio']);
    $internamento = Internamento::factory()->create(['data_saida' => '2026-02-10', 'destino_id' => $destino->id]);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'data_consulta_pos_alta' => '2026-02-01',
    ])->assertSessionHasErrors('data_consulta_pos_alta');

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'data_consulta_pos_alta' => '2026-02-15',
    ])->assertSessionDoesntHaveErrors();
});
