<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Role of the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Transactions created by this user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    /**
     * Check if user has a specific role by slug.
     */
    public function hasRole(string ...$slugs): bool
    {
        if (!$this->role) {
            return false;
        }

        return in_array($this->role->slug, $slugs, true);
    }

    /**
     * Check if user has a given permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->role) {
            return false;
        }

        return $this->role->hasPermission($permissionSlug);
    }

    /**
     * Check if user has access to a given module.
     */
    public function canAccessModule(string $module): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->role) {
            return false;
        }

        return $this->role->hasModuleAccess($module);
    }

    /**
     * Avatar URL accessor.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && file_exists(public_path($this->avatar))) {
            return asset($this->avatar);
        }

        // Clean UI initials avatar
        $initials = collect(explode(' ', $this->name))
            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0f172a&color=f8fafc&bold=true';
    }
}
