<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DestinoDetalhe extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'destino_id'];

    public function destino()
    {
        return $this->belongsTo(Destino::class, 'destino_id', 'id');
    }

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'destino_detalhe_id', 'id');
    }
}
