<?php

use Illuminate\Support\Str;

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

//activity Log 
function auditLog(
    string $action,
    array $properties = [],
    $subject = null
) {
    activity('audit')
        ->causedBy(auth()->user())
        ->performedOn($subject)
        ->withProperties(array_merge(
            $properties,
            [
 
            ]
        ))
        ->log($action);
}

if (! function_exists('impersonatorId')) {
    function impersonatorId(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $token = $user->currentAccessToken();

        if (! $token) {
            return null;
        }

        $ability = collect($token->abilities)
            ->first(fn ($a) => Str::startsWith($a, 'impersonator:'));

        return $ability
            ? (int) explode(':', $ability)[1]
            : null;
    }
}