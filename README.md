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

- `database/migrations/0001_01_01_000000_create_tenants_table.php`: creates `tenants` with UUID `id`, `name`, and optional `domain` (add more fields as needed).
- `app/Models/Tenant.php`: UUID primary key with auto-generated `id`, `users()` relation, and `$fillable` for tenant fields.
- `database/migrations/0001_01_01_000001_create_users_table.php`: adds `users.tenant_id` UUID with FK to `tenants.id`.
- `app/Models/User.php`: includes `tenant_id` in `$fillable`, auto-assigns it from the authenticated user on create, and defines `tenant()` relation.
- `app/Traits/AssignTenant.php`: fills `tenant_id` on create using the authenticated user.
- `app/Http/Controllers/Api/AuthController.php`: registration creates a tenant and its first user in a transaction; login blocks users without a tenant (except super admin).
- `config/permission.php`: enables Spatie teams (`'teams' => true`) and uses custom `Role`/`Permission` models.
- `database/migrations/2025_10_19_104201_create_permission_tables.php`: creates Spatie tables with teams enabled, and later migrations adjust the team key to UUIDs.
- `app/Http/Middleware/SetTenantPermission.php` + `routes/api.php`: sets `setPermissionsTeamId(auth()->user()->tenant_id)` on each request for protected routes.
- `app/Http/Controllers/Api/RoleController.php`, `app/Http/Controllers/Api/PermissionController.php`: create/update routes always write the current user's `tenant_id`.
- `database/seeders/RoleSeeder.php`: seeds global permissions and the base `tenant-admin` role.

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
- Super admin routes are grouped under `super.admin` middleware in `routes/api.php`.


