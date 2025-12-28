# Project-Structure-with-role-permissions

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
