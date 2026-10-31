<?php

use App\Models\Destino;
use App\Models\DestinoDetalhe;
use App\Models\Internamento;
use App\Models\User;

test('guarda destino_detalhe_id num internamento', function () {
    $user = User::factory()->create();
    $destino = Destino::factory()->create(['nome' => 'Domicílio']);
    $internamento = Internamento::factory()->create(['destino_id' => $destino->id]);
    $detalhe = DestinoDetalhe::factory()->create();

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'destino_detalhe_id' => $detalhe->id,
    ])->assertSessionDoesntHaveErrors();

    expect($internamento->refresh()->destino_detalhe_id)->toBe($detalhe->id);
});

test('exige servico_destino quando o destino é Transf Interna', function () {
    $user = User::factory()->create();
    $destinoTransfInterna = Destino::factory()->create(['nome' => 'Transf Interna']);
    $internamento = Internamento::factory()->create(['destino_id' => $destinoTransfInterna->id]);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'destino_id' => $destinoTransfInterna->id,
    ])->assertSessionHasErrors('servico_destino');

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'destino_id' => $destinoTransfInterna->id,
        'servico_destino' => 'Medicina Interna',
    ])->assertSessionDoesntHaveErrors();

    expect($internamento->refresh()->servico_destino)->toBe('Medicina Interna');
});

test('não exige servico_destino para outros destinos', function () {
    $user = User::factory()->create();
    $destino = Destino::factory()->create(['nome' => 'Domicílio']);
    $internamento = Internamento::factory()->create(['destino_id' => $destino->id]);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'destino_id' => $destino->id,
    ])->assertSessionDoesntHaveErrors();
});
