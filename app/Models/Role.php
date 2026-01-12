<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class Role extends SpatieRole
{
    public static function create(array $attributes = [])
    {
        // 1️⃣ Default guard
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

        // 2️⃣ Params to check duplicate
        $params = [
            'name'       => $attributes['name'],
            'guard_name' => $attributes['guard_name'],
        ];

        // 3️⃣ Tenant (team) support
        if (app(PermissionRegistrar::class)->teams) {
            $teamKey = app(PermissionRegistrar::class)->teamsKey;

            if (array_key_exists($teamKey, $attributes)) {
                $params[$teamKey] = $attributes[$teamKey];
            } else {
                $attributes[$teamKey] = getPermissionsTeamId();
            }
        }

        // 4️⃣ Prevent duplicate role in same tenant
        if (static::findByParam($params)) {
            throw RoleAlreadyExists::create(
                $attributes['name'],
                $attributes['guard_name']
            );
        }

        return static::query()->create($attributes);
    }

    public static function findByName(string $name, ?string $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $role = static::findByParam([
            'name'       => $name,
            'guard_name' => $guardName,
        ]);

        if (! $role) {
            throw RoleDoesNotExist::create($name, $guardName);
        }

        return $role;
    }

    public static function findById(int|string $id, ?string $guardName = null): RoleContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $role = static::findByParam([
            (new static)->getKeyName() => $id,
            'guard_name' => $guardName,
        ]);

        if (! $role) {
            throw RoleDoesNotExist::withId($id, $guardName);
        }

        return $role;
    }

    protected static function findByParam(array $params = []): ?RoleContract
    {
        $query = static::query();

        // 🔥 Tenant filter
        if (app(PermissionRegistrar::class)->teams) {
            $teamKey = app(PermissionRegistrar::class)->teamsKey;

            $query->where(
                fn($q) =>
                $q->whereNull($teamKey)
                    ->orWhere($teamKey, $params[$teamKey] ?? getPermissionsTeamId())
            );

            unset($params[$teamKey]);
        }

        // Apply remaining conditions
        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }

    public function scopeTenant(Builder $query): Builder
    {
        // 🔥 Super Admin → all permissions
        if (auth()->check() && auth()->user()?->is_super_admin) {
            return $query;
        }

        // ✅ get actual team column name (team_id)
        $teamKey = app(PermissionRegistrar::class)->teamsKey;
        // OR: config('permission.team_foreign_key');

        return $query->where($teamKey, auth()->user()->tenant_id);
    }
}
