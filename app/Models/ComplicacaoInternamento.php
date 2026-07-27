<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplicacaoInternamento extends Model
{
    protected $fillable = ['complicacao_id', 'internamento_id', 'resolucaos'];

    protected $table = 'complicacao_internamento';

    public function complicacao()
    {
        return $this->belongsTo(Complicacao::class, 'complicacao_id', 'id');
    }

    public function internamento()
    {
        return $this->belongsTo(Internamento::class, 'internamento_id', 'id');
    }

    public function resolucaos()
    {
        return $this->belongsToMany(Resolucao::class, 'complicacao_resolucao');
    }
}
