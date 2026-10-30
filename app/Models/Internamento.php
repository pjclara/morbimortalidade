<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Internamento extends Model
{
    /**
     * Causas selecionáveis para um reinternamento identificado como
     * readmissão (<30 dias após uma alta anterior do mesmo doente). Guardadas
     * em `causas_readmissao` como array json — um internamento pode ter mais
     * do que uma causa associada.
     */
    public const CAUSAS_READMISSAO_OPTIONS = [
        'complicacao_cirurgica',
        'infecao_ferida_operatoria',
        'complicacao_medica',
        'recidiva_agravamento',
        'efeito_adverso_medicacao',
        'problema_gestao_alta',
        'nao_relacionado',
        'outra',
    ];

    protected $fillable = ['bloquear', 'causas_readmissao', 'clavien_dindo_id', 'data_alta', 'data_entrada', 'data_saida', 'destino_id', 'dias_internamento', 'episodio', 'equipa_id', 'falecido', 'falecido_apos_alta', 'mortalidade_esperada', 'observacoes', 'origem_id', 'patient_id', 'responsavel_id'];

    protected $casts = [
        'causas_readmissao' => 'array',
    ];

    //

    public function clavienDindo()
    {
        return $this->belongsTo(ClavienDindo::class, 'clavien_dindo_id', 'id');
    }

    public function destino()
    {
        return $this->belongsTo(Destino::class, 'destino_id', 'id');
    }

    public function equipa()
    {
        return $this->belongsTo(Equipa::class, 'equipa_id', 'id');
    }

    public function origem()
    {
        return $this->belongsTo(Origem::class, 'origem_id', 'id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'id')->with('sexo');
    }

    public function blocoOperatorios()
    {
        return $this->hasMany(BlocoOperatorio::class, 'internamento_id', 'id')
            ->with('blocoOperatorioProcedimentos');
    }

    public function complicacaoInternamentos()
    {
        return $this->hasMany(ComplicacaoInternamento::class, 'internamento_id', 'id')->with('complicacao','resolucaos');
    }

    public function diagnosticoInternamentos()
    {
        return $this->hasMany(DiagnosticoInternamento::class, 'internamento_id', 'id')
            ->with('diagnostico');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id', 'id');
    }

    public function diagnosticos()
    {
        return $this->belongsToMany(Diagnostico::class)->withPivot('principal');
    }
    
}
