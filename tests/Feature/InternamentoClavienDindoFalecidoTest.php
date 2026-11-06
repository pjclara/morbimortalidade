<?php

use App\Models\BlocoOperatorio;
use App\Models\ClavienDindo;
use App\Models\Destino;
use App\Models\Internamento;
use App\Models\User;

// `id` não é fillable em ClavienDindo (ver app/Models/ClavienDindo.php), por
// isso ::create(['id' => ...]) ignora o id pedido — é preciso usar insert().
function criarClavienDindo(int $id, string $nome): ClavienDindo
{
    ClavienDindo::query()->insert(['id' => $id, 'nome' => $nome]);

    return ClavienDindo::findOrFail($id);
}

test('força Clavien-Dindo Grau V num internamento com bloco operatório e destino Falecido', function () {
    $user = User::factory()->create();
    $grauV = criarClavienDindo(ClavienDindo::GRAU_V_ID, 'Grau V');
    $destinoFalecido = Destino::factory()->create(['nome' => 'Falecido']);
    $internamento = Internamento::factory()->create([
        'destino_id' => $destinoFalecido->id,
        'falecido' => true,
        'clavien_dindo_id' => null,
    ]);
    BlocoOperatorio::create(['internamento_id' => $internamento->id]);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [])
        ->assertSessionDoesntHaveErrors();

    expect($internamento->refresh()->clavien_dindo_id)->toBe($grauV->id);
});

test('não permite "Sem complicações" num internamento com bloco operatório e destino Falecido', function () {
    $user = User::factory()->create();
    criarClavienDindo(ClavienDindo::GRAU_V_ID, 'Grau V');
    $semComplicacoes = criarClavienDindo(ClavienDindo::SEM_COMPLICACOES_ID, 'Sem complicações');
    $destinoFalecido = Destino::factory()->create(['nome' => 'Falecido']);
    $internamento = Internamento::factory()->create([
        'destino_id' => $destinoFalecido->id,
        'falecido' => true,
        'clavien_dindo_id' => null,
    ]);
    BlocoOperatorio::create(['internamento_id' => $internamento->id]);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'clavien_dindo_id' => $semComplicacoes->id,
    ])->assertSessionDoesntHaveErrors();

    expect($internamento->refresh()->clavien_dindo_id)->toBe(ClavienDindo::GRAU_V_ID);
});

test('mantém "Sem complicações" quando não há bloco operatório nem destino Falecido', function () {
    $user = User::factory()->create();
    $semComplicacoes = criarClavienDindo(ClavienDindo::SEM_COMPLICACOES_ID, 'Sem complicações');
    $destino = Destino::factory()->create(['nome' => 'Domicílio']);
    $internamento = Internamento::factory()->create([
        'destino_id' => $destino->id,
        'falecido' => false,
        'clavien_dindo_id' => null,
    ]);
    BlocoOperatorio::create(['internamento_id' => $internamento->id]);

    $this->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'clavien_dindo_id' => $semComplicacoes->id,
    ])->assertSessionDoesntHaveErrors();

    expect($internamento->refresh()->clavien_dindo_id)->toBe($semComplicacoes->id);
});
