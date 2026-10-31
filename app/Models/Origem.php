<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Origem extends Model
{
    use HasFactory;

    protected $fillable = ['nome'];

    //

    public function internamentos()
    {
        return $this->hasMany(Internamento::class, 'origem_id', 'id');
    }

}