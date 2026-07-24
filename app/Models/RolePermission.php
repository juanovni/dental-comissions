<?php

namespace App\Models;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'permission',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'permission' => UserPermission::class,
            'is_enabled' => 'boolean',
        ];
    }

    public static function enabledFor(UserRole|string|null $role, UserPermission|string $permission): bool
    {
        if ($role === null) {
            return false;
        }

        $role = $role instanceof UserRole ? $role->value : $role;
        $permission = $permission instanceof UserPermission ? $permission->value : $permission;

        $configuredPermission = self::query()
            ->where('role', $role)
            ->where('permission', $permission)
            ->first();

        if ($configuredPermission !== null) {
            return $configuredPermission->is_enabled;
        }

        return $role === UserRole::Admin->value;
    }
}
