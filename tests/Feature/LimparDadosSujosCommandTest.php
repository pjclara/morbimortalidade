<?php

use App\Models\Lotacao;
use App\Models\Origem;
use App\Models\TipoDeCirurgia;

test('dry-run reporta nomes inválidos sem alterar nada', function () {
    $tipo = TipoDeCirurgia::factory()->create(['nome' => '1.0']);
    $origem = Origem::factory()->create(['nome' => '13']);

    $this->artisan('app:limpar-dados-sujos')->assertExitCode(0);

    expect($tipo->fresh()->nome)->toBe('1.0');
    expect($origem->fresh()->nome)->toBe('13');
});

test('--lotacoes-2026-09 sem --numero-camas falha', function () {
    $this->artisan('app:limpar-dados-sujos', ['--lotacoes-2026-09' => true])
        ->assertExitCode(1);
});

test('--lotacoes-2026-09 --apply --numero-camas grava o período', function () {
    $this->artisan('app:limpar-dados-sujos', [
        '--lotacoes-2026-09' => true,
        '--numero-camas' => 65,
        '--apply' => true,
    ])->assertExitCode(0);

    expect(Lotacao::count())->toBe(1);
    expect(Lotacao::first()->numero_camas)->toBe(65);
});
