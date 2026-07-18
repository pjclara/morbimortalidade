<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['guard_name', 'name'];

    //

    public function modelHasPermissions()
    {
        return $this->hasMany(ModelHasPermission::class, 'permission_id', 'id');
    }

    public function roleHasPermissions()
    {
        return $this->hasMany(RoleHasPermission::class, 'permission_id', 'id');
    }

}