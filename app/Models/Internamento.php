<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Internamento extends Model
{
    protected $fillable = ['bloquear', 'clavien_dindo_id', 'data_alta', 'data_entrada', 'data_saida', 'destino_id', 'dias_internamento', 'episodio', 'equipa_id', 'falecido', 'mortalidade_esperada', 'observacoes', 'origem_id', 'patient_id', 'responsavel_id'];

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
        return $this->hasMany(ComplicacaoInternamento::class, 'internamento_id', 'id');
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
        return $this->belongsToMany(Diagnostico::class);
    }
}
