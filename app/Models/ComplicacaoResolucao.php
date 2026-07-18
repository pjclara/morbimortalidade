<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplicacaoResolucao extends Model
{
    protected $fillable = ['complicacao_internamento_id', 'resolucao_id'];

    //

    public function complicacaoInternamento()
    {
        return $this->belongsTo(ComplicacaoInternamento::class, 'complicacao_internamento_id', 'id');
    }

    public function resolucao()
    {
        return $this->belongsTo(Resolucao::class, 'resolucao_id', 'id');
    }

}