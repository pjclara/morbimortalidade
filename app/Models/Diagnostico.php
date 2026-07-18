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

}