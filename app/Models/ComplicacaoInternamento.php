<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplicacaoInternamento extends Model
{
    /**
     * Valores aceites para `momento`: quando a complicação surgiu em
     * relação à data de alta.
     */
    public const MOMENTO_OPTIONS = ['antes_alta', 'ate_30_dias', '31_90_dias', 'mais_90_dias'];

    protected $fillable = ['complicacao_id', 'internamento_id', 'resolucaos', 'momento'];

    protected $table = 'complicacao_internamento';

    public $timestamps = false;

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
        return $this->belongsToMany(
            Resolucao::class,
            'complicacao_resolucao',
            'complicacao_internamento_id',
            'resolucao_id'
        );
    }
}
