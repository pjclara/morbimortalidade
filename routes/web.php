<?php

use App\Http\Controllers\BlocoOperatorioController;
use App\Http\Controllers\BlocoOperatorioProcedimentoController;
use App\Http\Controllers\ClavienDindoController;
use App\Http\Controllers\ComplicacaoController;
use App\Http\Controllers\ComplicacaoInternamentoController;
use App\Http\Controllers\ComplicacaoResolucaoController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\DiagnosticoInternamentoController;
use App\Http\Controllers\EquipaController;
use App\Http\Controllers\FailedImportRowController;
use App\Http\Controllers\GrupoComplicacaoController;
use App\Http\Controllers\GrupoDiagnosticoController;
use App\Http\Controllers\GrupoProcedimentoController;
use App\Http\Controllers\InternamentoController;
use App\Http\Controllers\JobBatchController;
use App\Http\Controllers\OrigemController;
use App\Http\Controllers\PasswordResetTokenController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProcedimentoController;
use App\Http\Controllers\ResolucaoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SexoController;
use App\Http\Controllers\TipoDeCirurgiaController;
use App\Http\Controllers\UserController;
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
Route::resource('bloco_operatorio_procedimento', BlocoOperatorioProcedimentoController::class);
Route::resource('bloco_operatorios', BlocoOperatorioController::class);
Route::resource('clavien_dindos', ClavienDindoController::class);
Route::resource('complicacao_internamento', ComplicacaoInternamentoController::class);
Route::resource('complicacao_resolucao', ComplicacaoResolucaoController::class);
Route::resource('complicacaos', ComplicacaoController::class);
Route::resource('destinos', DestinoController::class);
Route::resource('diagnostico_internamento', DiagnosticoInternamentoController::class);
Route::resource('diagnosticos', DiagnosticoController::class);
Route::resource('equipas', EquipaController::class);
Route::resource('failed_import_rows', FailedImportRowController::class);
Route::resource('grupo_complicacaos', GrupoComplicacaoController::class);
Route::resource('grupo_diagnosticos', GrupoDiagnosticoController::class);
Route::resource('grupo_procedimentos', GrupoProcedimentoController::class);
Route::resource('internamentos', InternamentoController::class);
Route::resource('job_batches', JobBatchController::class);
Route::resource('origems', OrigemController::class);
Route::resource('password_reset_tokens', PasswordResetTokenController::class);
Route::resource('patients', PatientController::class);
Route::resource('permissions', PermissionController::class);
Route::resource('procedimentos', ProcedimentoController::class);
Route::resource('resolucaos', ResolucaoController::class);
Route::resource('roles', RoleController::class);
Route::resource('sexos', SexoController::class);
Route::resource('tipo_de_cirurgias', TipoDeCirurgiaController::class);
Route::resource('users', UserController::class);

// post route for importing internamentos
Route::post('/internamento/import', [InternamentoController::class, 'import'])
    ->name('internamento.import');
