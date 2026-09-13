<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo ICD-10-PCS — eixo 2 (sistema corporal): os 2 primeiros caracteres
 * do código de procedimento (secção + sistema corporal) mapeados para a
 * descrição legível usada nos filtros e na Analítica.
 */
class Icd10PcsAxis2Option extends Model
{
    protected $table = 'icd10_pcs_axis_2_options';

    protected $fillable = [
        'parent_prefix',
        'prefix',
        'value',
        'term',
        'description',
        'code_count',
    ];

    public function scopePrefix($query, $prefix)
    {
        return $query->where('prefix', $prefix);
    }
}
