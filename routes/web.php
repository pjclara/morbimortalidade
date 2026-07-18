<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
Route::resource('bloco_operatorio_procedimento', \App\Http\Controllers\BlocoOperatorioProcedimentoController::class);
Route::resource('bloco_operatorios', \App\Http\Controllers\BlocoOperatorioController::class);
Route::resource('clavien_dindos', \App\Http\Controllers\ClavienDindoController::class);
Route::resource('complicacao_internamento', \App\Http\Controllers\ComplicacaoInternamentoController::class);
Route::resource('complicacao_resolucao', \App\Http\Controllers\ComplicacaoResolucaoController::class);
Route::resource('complicacaos', \App\Http\Controllers\ComplicacaoController::class);
Route::resource('destinos', \App\Http\Controllers\DestinoController::class);
Route::resource('diagnostico_internamento', \App\Http\Controllers\DiagnosticoInternamentoController::class);
Route::resource('diagnosticos', \App\Http\Controllers\DiagnosticoController::class);
Route::resource('equipas', \App\Http\Controllers\EquipaController::class);
Route::resource('failed_import_rows', \App\Http\Controllers\FailedImportRowController::class);
Route::resource('grupo_complicacaos', \App\Http\Controllers\GrupoComplicacaoController::class);
Route::resource('grupo_diagnosticos', \App\Http\Controllers\GrupoDiagnosticoController::class);
Route::resource('grupo_procedimentos', \App\Http\Controllers\GrupoProcedimentoController::class);
Route::resource('internamentos', \App\Http\Controllers\InternamentoController::class);
Route::resource('job_batches', \App\Http\Controllers\JobBatchController::class);
Route::resource('origems', \App\Http\Controllers\OrigemController::class);
Route::resource('password_reset_tokens', \App\Http\Controllers\PasswordResetTokenController::class);
Route::resource('patients', \App\Http\Controllers\PatientController::class);
Route::resource('permissions', \App\Http\Controllers\PermissionController::class);
Route::resource('procedimentos', \App\Http\Controllers\ProcedimentoController::class);
Route::resource('resolucaos', \App\Http\Controllers\ResolucaoController::class);
Route::resource('roles', \App\Http\Controllers\RoleController::class);
Route::resource('sexos', \App\Http\Controllers\SexoController::class);
Route::resource('tipo_de_cirurgias', \App\Http\Controllers\TipoDeCirurgiaController::class);
Route::resource('users', \App\Http\Controllers\UserController::class);
