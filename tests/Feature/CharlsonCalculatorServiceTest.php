<?php

use App\Models\Complicacao;
use App\Models\ComplicacaoInternamento;
use App\Models\Diagnostico;
use App\Models\DiagnosticoInternamento;
use App\Models\Internamento;
use App\Models\Patient;
use App\Services\CharlsonCalculatorService;

function internamentoComIdade(int $idade, string $dataEntrada = '2026-06-15'): Internamento
{
    $dataNascimento = \Carbon\Carbon::parse($dataEntrada)->subYears($idade)->format('Y-m-d');

    return Internamento::factory()->create([
        'data_entrada' => $dataEntrada,
        'patient_id' => Patient::factory()->create(['data_nascimento' => $dataNascimento])->id,
    ]);
}

function marcarComComplicacao(Internamento $internamento): void
{
    $complicacao = Complicacao::create(['nome' => 'Infeção da ferida operatória']);

    ComplicacaoInternamento::create([
        'internamento_id' => $internamento->id,
        'complicacao_id' => $complicacao->id,
        'momento' => 'ate_30_dias',
    ]);
}

function adicionarComorbilidade(Internamento $internamento, string $codigo, bool $principal = false): void
{
    $diagnostico = Diagnostico::create(['nome' => $codigo, 'codigo' => $codigo]);

    DiagnosticoInternamento::create([
        'internamento_id' => $internamento->id,
        'diagnostico_id' => $diagnostico->id,
        'principal' => $principal,
        'descricao' => $codigo,
    ]);
}

test('devolve null quando o internamento não tem complicações registadas', function () {
    $internamento = internamentoComIdade(50);
    adicionarComorbilidade($internamento, 'J44'); // doença pulmonar crónica

    $resultado = (new CharlsonCalculatorService)->calcular($internamento);

    expect($resultado)->toBeNull();
});

test('internamento com complicações mas sem comorbilidades e idade < 40 tem score 0', function () {
    $internamento = internamentoComIdade(35);
    marcarComComplicacao($internamento);

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(0);
});

test('soma o peso da categoria da comorbilidade', function () {
    $internamento = internamentoComIdade(35);
    marcarComComplicacao($internamento);
    adicionarComorbilidade($internamento, 'J44'); // doença pulmonar crónica, peso 1

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(1);
});

test('diagnóstico principal não conta como comorbilidade', function () {
    $internamento = internamentoComIdade(35);
    marcarComComplicacao($internamento);
    adicionarComorbilidade($internamento, 'J44', principal: true);

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(0);
});

test('soma pontos de idade por década acima dos 40, até ao máximo de 4', function () {
    $internamento = internamentoComIdade(85);
    marcarComComplicacao($internamento);

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(4);
});

test('não duplica pontos quando há diabetes com e sem complicações — conta só a mais grave', function () {
    $internamento = internamentoComIdade(35);
    marcarComComplicacao($internamento);
    adicionarComorbilidade($internamento, 'E119'); // diabetes sem complicações, peso 1
    adicionarComorbilidade($internamento, 'E114'); // diabetes com complicações, peso 2

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(2);
});

test('soma categorias diferentes e idade', function () {
    $internamento = internamentoComIdade(62); // +3 pontos de idade (40-49:1,50-59:2,60-69:3)
    marcarComComplicacao($internamento);
    adicionarComorbilidade($internamento, 'J44'); // doença pulmonar crónica, peso 1
    adicionarComorbilidade($internamento, 'C80'); // neoplasia metastática, peso 6

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(1 + 6 + 3);
});

test('detalhar devolve categorias e pontos quando aplicável, null caso contrário', function () {
    $internamento = internamentoComIdade(35);
    adicionarComorbilidade($internamento, 'J44');

    expect((new CharlsonCalculatorService)->detalhar($internamento))->toBeNull();

    marcarComComplicacao($internamento);

    $detalhe = (new CharlsonCalculatorService)->detalhar($internamento);

    expect($detalhe['categorias'])->toBe(['doenca_pulmonar_cronica']);
    expect($detalhe['pontos_categorias'])->toBe(1);
    expect($detalhe['total'])->toBe(1);
});

test('reconhece categoria escolhida diretamente na checklist (código CHARLSON:<categoria>)', function () {
    $internamento = internamentoComIdade(35);
    marcarComComplicacao($internamento);
    adicionarComorbilidade($internamento, 'CHARLSON:doenca_renal'); // peso 2

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(2);
});

test('categoria direta e código ICD-10 da mesma categoria não duplicam pontos', function () {
    $internamento = internamentoComIdade(35);
    marcarComComplicacao($internamento);
    adicionarComorbilidade($internamento, 'CHARLSON:doenca_pulmonar_cronica');
    adicionarComorbilidade($internamento, 'J44');

    expect((new CharlsonCalculatorService)->calcular($internamento))->toBe(1);
});

test('opcoes() devolve as 17 categorias com rótulo e peso', function () {
    $opcoes = CharlsonCalculatorService::opcoes();

    expect($opcoes)->toHaveCount(17);
    expect($opcoes[0])->toHaveKeys(['key', 'label', 'peso']);

    $metastatica = collect($opcoes)->firstWhere('key', 'neoplasia_metastatica');
    expect($metastatica['peso'])->toBe(6);
    expect($metastatica['label'])->toBe('Neoplasia metastática');
});

test('Internamento::charlsonCalculado delega no serviço', function () {
    $internamento = internamentoComIdade(35);
    marcarComComplicacao($internamento);
    adicionarComorbilidade($internamento, 'J44');

    expect($internamento->charlsonCalculado())->toBe(1);
});
