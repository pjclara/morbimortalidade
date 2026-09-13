<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    protected $fillable = ['codigo', 'codigo_pai', 'descricao', 'grupo_diagnostico_id', 'nome'];

    //

    public function grupoDiagnostico()
    {
        return $this->belongsTo(GrupoDiagnostico::class, 'grupo_diagnostico_id', 'id');
    }

    public function diagnosticoInternamentos()
    {
        return $this->hasMany(DiagnosticoInternamento::class, 'diagnostico_id', 'id');
    }

    public function internamentos()
    {
        return $this->belongsToMany(Internamento::class);
    }

    // relationships with other models icd10_cm and icd10_capitulos_secoes

    public function icd10CM()
    {
        return $this->belongsTo(Icd10CM::class, 'codigo', 'code');
    }

    public function icd10CapitulosSecoes()
    {
        return $this->belongsTo(Icd10CapitulosSecoes::class, 'codigo', 'codigo_inicio');
    }

}
