<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlocoOperatorioProcedimento extends Model
{
    protected $table = 'bloco_operatorio_procedimento';

    protected $fillable = ['bloco_operatorio_id', 'descricao', 'procedimento_id'];

    public function blocoOperatorio()
    {
        return $this->belongsTo(BlocoOperatorio::class, 'bloco_operatorio_id', 'id');
    }

    public function procedimento()
    {
        return $this->belongsTo(Procedimento::class, 'procedimento_id', 'id');
    }

}