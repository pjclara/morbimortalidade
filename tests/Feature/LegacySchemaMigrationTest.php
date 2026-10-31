<?php

use App\Models\Internamento;
use App\Models\Patient;

test('legacy schema migration creates usable tables for factories', function () {
    $internamento = Internamento::factory()->create();

    expect($internamento->patient)->toBeInstanceOf(Patient::class);
    expect($internamento->data_entrada)->not->toBeNull();
});
