# Multi-Tenant-Role-Permission-Management-System

REST API for multi-tenant role/permission management with Sanctum token auth and Spatie teams.

## Requirements

- PHP 8.2+
- Composer
- MySQL or another supported database

## Setup

```bash
composer install
cp .env.example .env
# PowerShell: Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=SuperAdminSeeder # optional
php artisan serve
```

Super admin credentials (if seeded):

- Email: admin@gmail.com
- Password: 12345678

## Multi-Tenancy Setup

This project uses a single database with per-tenant scoping via `tenant_id`, plus Spatie Permission "teams" so roles and permissions are isolated by tenant.

Key pieces:

- `create_tenants_table`: creates `tenants` with UUID `id`, `name`, and optional `domain` (add more fields as needed).
- `app/Models/User.php`: includes `tenant_id` in `$fillable`, auto-assigns it from the authenticated user on create, and defines `tenant()` relation.
- `app/Traits/AssignTenant.php`: fills `tenant_id` on create; if a table has `branch_id`, it also assigns `branch_id` from `active_branch_id` (skipped for super/support admins).
- `app/Traits/BelongsToTenant.php` + `app/Models/TenantModel.php`: applies a global scope for `tenant_id` (and `branch_id` when present), auto-assigns tenant/branch on create, and protects `tenant_id`/`branch_id` from being changed on update for non-admins.
- `AuthController.php`: registration creates a tenant and its first user in a transaction; login blocks users without a tenant (except super admin).
- `config/permission.php`: enables Spatie teams (`'teams' => true`) and uses custom `Role`/`Permission` models.
- `create_permission_tables.php`: creates Spatie tables with teams enabled, and later migrations adjust the team key to UUIDs.
- `Middleware/SetTenantPermission.php` + `routes/api.php`: sets `setPermissionsTeamId(auth()->user()->tenant_id)` on each request for protected routes.
- `app/Http/Controllers/Api/RoleController.php`, `app/Http/Controllers/Api/PermissionController.php`: create/update routes always write the current user's `tenant_id`.

## Tenant-Scoped Permission Fixes (important)

These changes prevent cross-tenant permission lookups and 403 errors when multiple organizations exist:

- `app/Models/Permission.php`: overrides Spatie lookup helpers (`findByName`, `findById`, `create`, `findOrCreate`) to always include the current team/tenant. Without this, Spatie can resolve the first tenant's permission record and deny access for other tenants.
- `app/Http/Controllers/Api/AuthController.php`: creates roles/permissions using `config('permission.team_foreign_key')` (not hardcoded), so data is stored with the correct tenant key.
- `app/Http/Controllers/Api/RoleController.php`, `app/Http/Controllers/Api/PermissionController.php`, `app/Http/Controllers/Api/UserController.php`: validation now scopes `unique`/`exists` rules to the current tenant to avoid cross-tenant conflicts.

## Super Admin (bypass permissions)

Super admins bypass all permission checks and tenant restrictions:

- `database/migrations/2026_01_01_105659_add_is_super_admin_to_users_table.php`: adds `is_super_admin`.
- `app/Providers/AuthServiceProvider.php`: `Gate::before` returns `true` for super admins, granting all abilities.
- `app/Http/Controllers/Api/AuthController.php`: login skips the tenant check for super admins.
- `app/Models/User.php`: `scopeTenant()` returns all users for super admins.
- `app/Http/Middleware/SuperAdminMiddleware.php` + `routes/api.php`: `super.admin` middleware protects super-admin-only routes.

To create a super admin:

```bash
php artisan db:seed --class=SuperAdminSeeder
```

This seeds a global `super-admin` role, assigns all permissions, and sets `is_super_admin = true` for `admin@gmail.com`.

## Impersonation (super admin)

Super admins can impersonate a tenant owner to troubleshoot tenant-scoped behavior. The impersonation token carries tenant context and expires after 30 minutes.

Flow:

1. Use a super admin token to request an impersonation token.
2. Use the returned impersonation token as `Authorization: Bearer <token>` for tenant requests.
3. Call the exit endpoint with the impersonation token to end the session (deletes the token).

Endpoints:

- POST `/api/admin/impersonate/{tenant}` (super admin only)
- POST `/api/impersonation/exit` (requires impersonation token)

Notes:

- The tenant is resolved from the token ability `tenant_id:<uuid>` (`app/Http/Middleware/ResolveTenantFromToken.php`).
- The impersonation token is issued to the tenant owner and includes abilities: `impersonate`, `tenant_id:<uuid>`, and `impersonator:<id>`.

## Authentication

- POST `/api/register`
- POST `/api/login` (returns token)
- Add header: `Authorization: Bearer <token>` for protected routes
- POST `/api/logout`
- GET `/api/user`
- GET `/api/me`

## Roles and Permissions (brief)

This project uses `spatie/laravel-permission`. The permission tables are created by migration
`database/migrations/2025_10_19_104201_create_permission_tables.php`.

Quick flow:

1. Login as the super admin (if seeded) or any tenant user to get a token.
2. Create permissions: POST `/api/permissions` with `{ "name": "view-categories" }`.
3. Create a role with permissions: POST `/api/roles` with `{ "name": "admin", "permissions": ["view-categories"] }`.
4. Assign the role to a user: POST `/api/users/{id}/assign-role` with `{ "role": "admin" }`.
5. Verify with GET `/api/me`.

Seeded global permissions (via `RoleSeeder`):

- `manage_users`
- `manage_roles`
- `manage_permissions`

Permission names used by controllers:

- Authors: `view-authors`, `create-authors`, `update-authors`, `delete-authors`
- Categories: `view-categories`, `create-categories`, `update-categories`, `delete-categories`
- Users: `manage_users`

## API Routes

All routes below require `auth:sanctum` and `tenant.permission` unless noted.

- Auth: `/api/register`, `/api/login`
- Session: `/api/logout`, `/api/user`, `/api/me`
- Users: `/api/users`, `/api/users/{id}/*` (role/permission management)
- Roles: `/api/roles`
- Permissions: `/api/permissions`
- Authors: `/api/authors`
- Categories: `/api/categories`
- Impersonation: `/api/admin/impersonate/{tenant}`, `/api/impersonation/exit`
- Super admin routes are grouped under `super.admin` middleware in `routes/api.php`.

## New Module Checklist (avoid permission breakage)

- Define permission names for each action (example: `view-products`, `create-products`, `update-products`, `delete-products`).
- Create permissions with the tenant key (`config('permission.team_foreign_key')`), not hardcoded `team_id`.
- Ensure `SetTenantPermission` runs after auth and before `permission`/`role` middleware (see `bootstrap/app.php` priority).
- Put new routes inside the `auth:sanctum` + `tenant.permission` group.
- Add `permission:` middleware to controllers per action.
- Scope `unique`/`exists` validation rules by tenant in controllers.
- Reset permission cache after adding new permissions: `php artisan permission:cache-reset`.
- Keep `guard_name` consistent (typically `web`) across roles/permissions.

## Branch Scoping (optional)

If your tables include `branch_id`, scoping is enforced alongside `tenant_id`:

- `BelongsToTenant` global scope limits results to the current tenant and (when applicable) the user's `active_branch_id`.
- `AssignTenant` auto-sets `branch_id` on create for non-super/support admins.
- Updates to `tenant_id` or `branch_id` are ignored for non-admins (values revert to originals).
