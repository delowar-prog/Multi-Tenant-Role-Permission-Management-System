<?php

function tenantFeature(string $key, $default = null): mixed
{
    $user = auth()->user();

    if (!$user || !$user->tenant) {
        return $default;
    }

    return $user->tenant->feature($key, $default);
}

function tenant(): mixed
{
    return app()->bound('tenant')
        ? app('tenant')
        : null;
}