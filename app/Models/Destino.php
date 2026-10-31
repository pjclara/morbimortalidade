<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    use HasFactory;

    protected $fillable = ['nome'];

    //

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'destino_id', 'id');
    }

}