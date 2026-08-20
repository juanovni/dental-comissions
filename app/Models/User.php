<?php

namespace App\Models;

use App\Enums\UserPermission;
use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'admin',
        'is_active' => true,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'professional_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function getTenants(Panel $panel): array | Collection
    {
        if ($this->isSuperAdmin()) {
            return Clinic::query()->orderBy('name')->get();
        }

        return $this->clinics()
            ->wherePivot('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function canAccessTenant(\Illuminate\Database\Eloquent\Model $tenant): bool
    {
        if (! $tenant instanceof Clinic) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->clinics()
            ->whereKey($tenant->getKey())
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function getDefaultTenant(Panel $panel): ?\Illuminate\Database\Eloquent\Model
    {
        if ($this->isSuperAdmin()) {
            return Clinic::query()->orderBy('name')->first();
        }

        $defaultClinic = $this->clinics()
            ->wherePivot('is_active', true)
            ->wherePivot('is_default', true)
            ->orderBy('name')
            ->first();

        if ($defaultClinic) {
            return $defaultClinic;
        }

        return $this->clinics()
            ->wherePivot('is_active', true)
            ->orderBy('name')
            ->first();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function hasRolePermission(UserPermission|string $permission): bool
    {
        return RolePermission::enabledFor($this->role, $permission);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class)
            ->withPivot(['role', 'is_default', 'is_active', 'permissions'])
            ->withTimestamps();
    }

    public function socialCommentActions(): HasMany
    {
        return $this->hasMany(SocialCommentAction::class, 'performed_by');
    }

    public function assignedAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'assigned_user_id');
    }

    public function createdAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }
}
