<?php

use App\Http\Controllers\BlocoOperatorioController;
use App\Http\Controllers\BlocoOperatorioProcedimentoController;
use App\Http\Controllers\ClavienDindoController;
use App\Http\Controllers\ComplicacaoController;
use App\Http\Controllers\ComplicacaoInternamentoController;
use App\Http\Controllers\ComplicacaoResolucaoController;
use App\Http\Controllers\DashboardCirurgiaController;
use App\Http\Controllers\DestinoController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\DiagnosticoInternamentoController;
use App\Http\Controllers\DistribuicaoController;
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
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SexoController;
use App\Http\Controllers\TipoDeCirurgiaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';


Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardCirurgiaController::class, 'index'])
        ->name('dashboard');


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

    Route::get('/roles', fn() => Role::all());

    // post route for importing internamentos
    Route::post('/internamento/import', [InternamentoController::class, 'import'])
        ->name('internamento.import');
    // post route for importing internamentos
    Route::post('/internamento/importBloco', [InternamentoController::class, 'importBloco'])
        ->name('internamento.importBloco');

    Route::get('/admin/users', [UserRoleController::class, 'index'])->name('admin.users');
    Route::post('/admin/users/{user}/roles', [UserRoleController::class, 'updateRoles'])->name('admin.users.roles');
    Route::resource('admin/users', UserController::class)
        ->only(['store', 'update', 'destroy'])
        ->names([
            'store' => 'admin.users.store',
            'update' => 'admin.users.update',
            'destroy' => 'admin.users.destroy',
        ]);

    Route::get('/distribuicao', [DistribuicaoController::class, 'index'])->middleware(['auth']);;
    Route::get('/distribuicao/simular', [DistribuicaoController::class, 'simular'])->middleware(['auth']);;
    Route::post('/distribuicao/executar', [DistribuicaoController::class, 'executar'])->middleware(['auth']);;
});


// Gestão de RBAC (roles & permissions)
Route::get('/access-control', [RolePermissionController::class, 'index']);

Route::post('/access-control/roles', [RolePermissionController::class, 'storeRole'])
    ->middleware('permission:users.manage');
Route::put('/access-control/roles/{role}', [RolePermissionController::class, 'updateRole'])
    ->middleware('permission:users.manage');
Route::delete('/access-control/roles/{role}', [RolePermissionController::class, 'destroyRole'])
    ->middleware('permission:users.manage');

Route::post('/access-control/permissions', [RolePermissionController::class, 'storePermission'])
    ->middleware('permission:users.manage');
Route::put('/access-control/permissions/{permission}', [RolePermissionController::class, 'updatePermission'])
    ->middleware('permission:users.manage');
Route::delete('/access-control/permissions/{permission}', [RolePermissionController::class, 'destroyPermission'])
    ->middleware('permission:users.manage');
