<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlocoOperatorio extends Model
{
    protected $fillable = ['ambulatorio', 'bloco_num', 'data_intervencao', 'internamento_id', 'tipo_de_cirurgia_id'];

    //

    public function internamento()
    {
        return $this->belongsTo(Internamento::class, 'internamento_id', 'id');
    }

    public function tipoDeCirurgia()
    {
        return $this->belongsTo(TipoDeCirurgia::class, 'tipo_de_cirurgia_id', 'id');
    }

    public function blocoOperatorioProcedimentos()
    {
        return $this->hasMany(BlocoOperatorioProcedimento::class, 'bloco_operatorio_id', 'id')
            ->with('procedimento');
    }

    public function procedimentos()
    {
        return $this->belongsToMany(Procedimento::class);
    }
}
