<?php

use App\Models\AtrasoAlta;
use App\Models\Internamento;
use App\Models\Lotacao;
use App\Models\MotivoAtraso;
use App\Models\Origem;
use App\Models\Patient;
use App\Services\IndicadoresServicoService;

function serviceParaPeriodo(string $inicio, string $fim): IndicadoresServicoService
{
    return new IndicadoresServicoService($inicio, $fim);
}

test('altasPorDiaSemana calcula percentagens de sábado/domingo', function () {
    // 2026-01-03 é sábado, 2026-01-04 é domingo, 2026-01-05 é segunda.
    Internamento::factory()->create(['data_saida' => '2026-01-03']);
    Internamento::factory()->create(['data_saida' => '2026-01-04']);
    Internamento::factory()->create(['data_saida' => '2026-01-05']);
    Internamento::factory()->create(['data_saida' => '2026-01-05']);

    $resultado = serviceParaPeriodo('2026-01-01', '2026-01-31')->altasPorDiaSemana();

    expect($resultado['total'])->toBe(4);
    expect($resultado['percentagem_sabado'])->toBe(25.0);
    expect($resultado['percentagem_domingo'])->toBe(25.0);
    expect($resultado['percentagem_fim_de_semana'])->toBe(50.0);
});

test('demoraMedia distingue programados de urgentes e conta estadias longas', function () {
    $origemUrgencia = Origem::factory()->create(['nome' => 'Urgência']);
    $origemProgramada = Origem::factory()->create(['nome' => 'Programado']);

    Internamento::factory()->create(['data_saida' => '2026-01-10', 'origem_id' => $origemUrgencia->id, 'dias_internamento' => 20]);
    Internamento::factory()->create(['data_saida' => '2026-01-10', 'origem_id' => $origemProgramada->id, 'dias_internamento' => 4]);
    Internamento::factory()->create(['data_saida' => '2026-01-10', 'origem_id' => $origemProgramada->id, 'dias_internamento' => 6]);

    $resultado = serviceParaPeriodo('2026-01-01', '2026-01-31')->demoraMedia();

    expect($resultado['media_urgentes'])->toBe(20.0);
    expect($resultado['media_programados'])->toBe(5.0);
    expect($resultado['estadias_longas_n'])->toBe(1);
});

test('atrasoAltaClinica soma dias de atraso e agrupa por motivo', function () {
    $motivo = MotivoAtraso::factory()->create(['nome' => 'Aguarda transporte']);

    $internamento = Internamento::factory()->create([
        'data_saida' => '2026-01-20',
        'data_alta' => '2026-01-15',
    ]);
    AtrasoAlta::factory()->for($internamento, 'internamento')->create(['motivo_id' => $motivo->id]);

    Internamento::factory()->create(['data_saida' => '2026-01-10', 'data_alta' => '2026-01-10']);

    $resultado = serviceParaPeriodo('2026-01-01', '2026-01-31')->atrasoAltaClinica();

    expect($resultado['total_dias_atraso'])->toBe(5);
    expect($resultado['doentes_com_atraso'])->toBe(1);
    expect($resultado['por_motivo'])->toBe(['Aguarda transporte' => 1]);
});

test('readmissoes30Dias conta readmissões e planeadas/relacionadas', function () {
    $patient = Patient::factory()->create();

    Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-01',
        'data_saida' => '2026-01-05',
    ]);

    Internamento::factory()->create([
        'patient_id' => $patient->id,
        'data_entrada' => '2026-01-10',
        'data_saida' => '2026-01-15',
        'readmissao_planeada' => false,
        'readmissao_relacionada' => true,
    ]);

    Internamento::factory()->create([
        'data_entrada' => '2026-01-20',
        'data_saida' => '2026-01-25',
    ]);

    $resultado = serviceParaPeriodo('2026-01-01', '2026-01-31')->readmissoes30Dias();

    expect($resultado['total_internamentos'])->toBe(3);
    expect($resultado['n_readmissoes'])->toBe(1);
    expect($resultado['nao_planeadas'])->toBe(1);
    expect($resultado['relacionadas'])->toBe(1);
});

test('ocupacaoDiaria conta dias acima da lotação', function () {
    Lotacao::factory()->create(['data_inicio' => '2026-01-01', 'data_fim' => null, 'numero_camas' => 1]);

    Internamento::factory()->create(['data_entrada' => '2026-01-01', 'data_saida' => '2026-01-05']);
    Internamento::factory()->create(['data_entrada' => '2026-01-02', 'data_saida' => '2026-01-03']);

    $resultado = serviceParaPeriodo('2026-01-01', '2026-01-05')->ocupacaoDiaria();

    // 2026-01-02 e 2026-01-03: os dois internamentos estão presentes (2 > 1 cama).
    expect($resultado['dias_acima_lotacao'])->toBe(2);
});
