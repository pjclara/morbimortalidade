<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoAtraso extends Model
{
    use HasFactory;

    protected $table = 'motivos_atraso';

    protected $fillable = ['nome', 'ativo'];

    public function atrasosAlta()
    {
        return $this->hasMany(AtrasoAlta::class, 'motivo_id', 'id');
    }
}
