# Multi-Tenant-Role-Permission-Management-System

REST API for managing authors, categories, and publishers with token auth and role/permission access control.

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
php artisan serve
```

Seeded admin user:

- Email: admin@gmail.com
- Password: 12345678

## Multi-Tenancy Setup

This project uses a single database with per-tenant scoping via `tenant_id`, plus Spatie Permission "teams" so roles and permissions are isolated by tenant.

Key pieces:

- `database/migrations/0001_01_01_000000_create_tenants_table.php`: creates `tenants` with UUID `id`, `name`, and optional `domain` (add more fields as needed).
- `app/Models/Tenant.php`: UUID primary key with auto-generated `id`, `users()` relation, and `$fillable` for tenant fields.
- `database/migrations/0001_01_01_000001_create_users_table.php`: adds `users.tenant_id` UUID with FK to `tenants.id`.
- `app/Models/User.php`: includes `tenant_id` in `$fillable`, auto-assigns it from the authenticated user on create, and defines `tenant()` relation.
- `app/Http/Controllers/Api/AuthController.php`: registration creates a tenant and its first user in a transaction; login blocks users without a tenant.
- `config/permission.php`: enables Spatie teams (`'teams' => true`) with `team_foreign_key` set to `tenant_id`, and uses custom `Role`/`Permission` models.
- `app/Models/Role.php`, `app/Models/Permission.php`: extend Spatie models and apply the `BelongsToTenant` trait.
- `app/Traits/BelongsToTenant.php`: adds a global `tenant_id` scope and auto-assigns `tenant_id` on create.
- `app/Http/Middleware/SetTenantPermission.php` + `bootstrap/app.php`: sets `setPermissionsTeamId(auth()->user()->tenant_id)` on each request.
- `database/migrations/2025_12_28_112248_add_tenant_id_to_roles_table.php`, `database/migrations/2025_12_28_112303_add_tenant_id_to_permissions_table.php`, `database/migrations/2025_12_29_135433_add_tenant_id_to_spatie_pivot_tables.php`: add `tenant_id` columns required by the teams setup.
- `app/Http/Controllers/Api/RoleController.php`, `app/Http/Controllers/Api/PermissionController.php`: create/update routes always write the current user's `tenant_id`.
- `database/seeders/TenantSeeder.php` + `database/seeders/DatabaseSeeder.php`: seed a default tenant and the admin user.

## Authentication

- POST `/api/register`
- POST `/api/login` (returns token)
- Add header: `Authorization: Bearer <token>` for protected routes
- POST `/api/logout`

## Roles and Permissions (brief)

This project uses `spatie/laravel-permission`. The permission tables are created by migration
`database/migrations/2025_10_19_104201_create_permission_tables.php`.

Quick flow:

1. Login as the seeded admin to get a token.
2. Create permissions: POST `/api/permissions` with `{ "name": "view-categories" }`.
3. Create a role with permissions: POST `/api/roles` with `{ "name": "admin", "permissions": ["view-categories"] }`.
4. Assign the role to a user: POST `/api/users/{id}/assign-role` with `{ "role": "admin" }`.
5. Verify with GET `/api/me`.

Permission names used by controllers:

- Authors: `view-authors`, `create-authors`, `update-authors`, `delete-authors`
- Categories: `view-categories`, `create-categories`, `update-categories`, `delete-categories`
- Publishers: `view-publishers`, `create-publishers`, `update-publishers`, `delete-publishers`
- Reports: `view-report`

## API Routes

All routes below require `auth:sanctum` unless noted.

- Auth: `/api/register`, `/api/login`, `/api/logout`, `/api/me`
- Users: `/api/users`, `/api/users/{id}/*` (role/permission management)
- Roles: `/api/roles`
- Permissions: `/api/permissions`
- Authors: `/api/authors`
- Categories: `/api/categories`
- Publishers: `/api/publishers`
- Protected by role: GET `/api/admin/dashboard` (role: `admin`)
- Protected by permission: GET `/api/report` (permission: `view-report`)


