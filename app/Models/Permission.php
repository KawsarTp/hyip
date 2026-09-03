<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $fillable = ['key', 'name', 'group_name', 'description'];

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }
}
