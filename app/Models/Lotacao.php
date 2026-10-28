<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lotacao extends Model
{
    protected $table = 'lotacoes';

    protected $fillable = ['data_inicio', 'data_fim', 'numero_camas', 'observacoes'];

    protected function casts(): array
    {
        return [
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }
}
