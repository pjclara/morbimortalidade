<?php

use App\Models\Diagnostico;
use App\Models\DiagnosticoInternamento;
use App\Models\Internamento;
use App\Models\User;

function putComorbilidades(Internamento $internamento, array $comorbilidades)
{
    $user = User::factory()->create();

    return test()->actingAs($user)->put("/internamentos/{$internamento->id}", [
        'comorbilidades' => $comorbilidades,
    ]);
}

test('adiciona uma comorbilidade nova, criando o diagnóstico se necessário', function () {
    $internamento = Internamento::factory()->create();

    putComorbilidades($internamento, [
        ['codigo' => 'J449', 'descricao' => 'DPOC'],
    ])->assertSessionHasNoErrors();

    $diagnostico = Diagnostico::where('codigo', 'J449')->first();
    expect($diagnostico)->not->toBeNull();

    $this->assertDatabaseHas('diagnostico_internamento', [
        'internamento_id' => $internamento->id,
        'diagnostico_id' => $diagnostico->id,
        'principal' => false,
        'descricao' => 'DPOC',
    ]);
});

test('reenviar a mesma comorbilidade (com id) não duplica a linha', function () {
    $internamento = Internamento::factory()->create();
    $diagnostico = Diagnostico::create(['codigo' => 'E119', 'nome' => 'Diabetes']);

    $existente = DiagnosticoInternamento::create([
        'internamento_id' => $internamento->id,
        'diagnostico_id' => $diagnostico->id,
        'principal' => false,
        'descricao' => 'Diabetes',
    ]);

    putComorbilidades($internamento, [
        ['id' => $existente->id, 'codigo' => 'E119', 'descricao' => 'Diabetes'],
    ])->assertSessionHasNoErrors();

    expect(DiagnosticoInternamento::where('internamento_id', $internamento->id)->where('principal', false)->count())->toBe(1);
});

test('omitir uma comorbilidade existente na lista enviada remove-a', function () {
    $internamento = Internamento::factory()->create();
    $diagnostico = Diagnostico::create(['codigo' => 'E119', 'nome' => 'Diabetes']);

    $existente = DiagnosticoInternamento::create([
        'internamento_id' => $internamento->id,
        'diagnostico_id' => $diagnostico->id,
        'principal' => false,
        'descricao' => 'Diabetes',
    ]);

    putComorbilidades($internamento, [])->assertSessionHasNoErrors();

    expect(DiagnosticoInternamento::find($existente->id))->toBeNull();
});

test('não mexe no diagnóstico principal ao sincronizar comorbilidades', function () {
    $internamento = Internamento::factory()->create();
    $diagnosticoPrincipal = Diagnostico::create(['codigo' => 'K358', 'nome' => 'Apendicite']);

    $principal = DiagnosticoInternamento::create([
        'internamento_id' => $internamento->id,
        'diagnostico_id' => $diagnosticoPrincipal->id,
        'principal' => true,
        'descricao' => 'Apendicite',
    ]);

    putComorbilidades($internamento, [
        ['codigo' => 'J449', 'descricao' => 'DPOC'],
    ])->assertSessionHasNoErrors();

    expect(DiagnosticoInternamento::find($principal->id))->not->toBeNull();
});
