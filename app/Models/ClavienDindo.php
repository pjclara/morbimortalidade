<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClavienDindo extends Model
{
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