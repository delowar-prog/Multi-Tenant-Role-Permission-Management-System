<?php

namespace App\Models;

use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Eloquent\Builder;

class Permission extends SpatiePermission
{
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

        $params = ['name' => $attributes['name'], 'guard_name' => $attributes['guard_name']];
        if (app(PermissionRegistrar::class)->teams) {
            $teamsKey = app(PermissionRegistrar::class)->teamsKey;

            if (array_key_exists($teamsKey, $attributes)) {
                $params[$teamsKey] = $attributes[$teamsKey];
            } else {
                $attributes[$teamsKey] = getPermissionsTeamId();
            }
        }

        if (static::findByParam($params)) {
            throw PermissionAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    public static function findByName(string $name, ?string $guardName = null): PermissionContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $permission = static::findByParam(['name' => $name, 'guard_name' => $guardName]);

        if (! $permission) {
            throw PermissionDoesNotExist::create($name, $guardName);
        }

        return $permission;
    }

    public static function findById(int|string $id, ?string $guardName = null): PermissionContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $permission = static::findByParam([(new static)->getKeyName() => $id, 'guard_name' => $guardName]);

        if (! $permission) {
            throw PermissionDoesNotExist::withId($id, $guardName);
        }

        return $permission;
    }

    public static function findOrCreate(string $name, ?string $guardName = null): PermissionContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $permission = static::findByParam(['name' => $name, 'guard_name' => $guardName]);

        if (! $permission) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName] + (app(PermissionRegistrar::class)->teams ? [app(PermissionRegistrar::class)->teamsKey => getPermissionsTeamId()] : []));
        }

        return $permission;
    }

    protected static function findByParam(array $params = []): ?PermissionContract
    {
        $query = static::query();

        if (app(PermissionRegistrar::class)->teams) {
            $teamsKey = app(PermissionRegistrar::class)->teamsKey;

            $query->where(
                fn($q) => $q->whereNull($teamsKey)
                    ->orWhere($teamsKey, $params[$teamsKey] ?? getPermissionsTeamId())
            );
            unset($params[$teamsKey]);
        }

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
