<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClavienDindo extends Model
{
    // Grau usado para assinalar explicitamente "sem complicações" num
    // internamento com bloco operatório, sem exigir complicações registadas.
    const SEM_COMPLICACOES_ID = 10;

    protected $fillable = ['descricao', 'nome'];

    //

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'clavien_dindo_id', 'id');
    }

    public function resolucaos()
    {
        return $this->hasMany(Resolucao::class, 'clavien_dindo_id', 'id');
    }

}