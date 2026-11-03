<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Users associated with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Permissions assigned to this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    /**
     * Check if the role has a given permission slug.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super admin has all permissions
        if ($this->slug === 'super_admin') {
            return true;
        }

        return $this->permissions->contains('slug', $permissionSlug);
    }

    /**
     * Check if role has access to a specific module.
     */
    public function hasModuleAccess(string $module): bool
    {
        if ($this->slug === 'super_admin') {
            return true;
        }

        return $this->permissions->contains('module', $module);
    }
}

