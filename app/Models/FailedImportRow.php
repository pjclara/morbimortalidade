<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedImportRow extends Model
{
    protected $fillable = ['data', 'import_id', 'validation_error'];

    //

    public function import()
    {
        return $this->belongsTo(Import::class, 'import_id', 'id');
    }

}