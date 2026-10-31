<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipa extends Model
{
    use HasFactory;

    protected $fillable = ['abrv', 'nome'];

    //

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'equipa_id', 'id');
    }

}