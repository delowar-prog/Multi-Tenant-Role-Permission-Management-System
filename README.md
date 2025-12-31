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

এই প্রজেক্টে টেন্যান্ট বেসড আইসোলেশন + Spatie permission "teams" ফিচার ব্যবহার করেছি। নিচে কোথায় কী বদল করেছি আর কোনটা কী কাজে লাগে — ভবিষ্যতে অন্য প্রজেক্টে সেটআপ করলে এখান থেকেই গাইড পাবেন:

- `database/migrations/0001_01_01_000000_create_tenants_table.php`: `tenants` টেবিল (UUID `id`, optional `domain`) তৈরি করে; প্রতিটি টেন্যান্ট/অর্গানাইজেশনের রেকর্ড এখানেই থাকে।
- `app/Models/Tenant.php`: UUID প্রাইমারি কি জেনারেট করে; `Tenant::users()` রিলেশন আছে; দরকার হলে `domain`/extra fields `fillable`-এ যোগ করবেন।
- `database/migrations/0001_01_01_000001_create_users_table.php`: `users.tenant_id` FK যোগ; ইউজার সবসময় একটি টেন্যান্টের সাথে বাঁধা থাকে।
- `app/Models/User.php`: নতুন ইউজার create হলে (auth থাকলে) `tenant_id` auto-assign হয়; `tenant()` রিলেশন আছে।
- `app/Http/Controllers/Api/AuthController.php`: register এ একই ট্রানজ্যাকশনে tenant + user create; login এ tenant না থাকলে ব্লক — আপনার নিজের tenant-creation flow চাইলে এখানেই বদলাবেন।
- `config/permission.php`: Spatie teams enable (`'teams' => true`) এবং `team_foreign_key` হিসাবে `tenant_id`; সাথে custom `Role`/`Permission` model ব্যবহার করা।
- `app/Models/Role.php`, `app/Models/Permission.php`: Spatie model extend করে `BelongsToTenant` trait যুক্ত, যাতে role/permission tenant-wise scoped থাকে।
- `app/Traits/BelongsToTenant.php`: Global scope দিয়ে সব query-তে `tenant_id` filter করে এবং create করার সময় `tenant_id` auto-assign করে; অন্য যেকোন tenant-scoped model-এ এই trait দিন।
- `app/Http/Middleware/SetTenantPermission.php` + `bootstrap/app.php`: প্রতিটি request এ `setPermissionsTeamId(auth()->user()->tenant_id)` সেট করা হয়—Spatie permission teams ঠিকভাবে কাজ করার জন্য এটি বাধ্যতামূলক।
- `database/migrations/2025_12_28_112248_add_tenant_id_to_roles_table.php`, `database/migrations/2025_12_28_112303_add_tenant_id_to_permissions_table.php`, `database/migrations/2025_12_29_135433_add_tenant_id_to_spatie_pivot_tables.php`: roles/permissions/pivot এ `tenant_id` সংরক্ষণ; যদি আপনি UUID ব্যবহার করেন, Spatie migration-এ `team_foreign_key` কলাম টাইপ UUID রাখবেন (টাইপ mismatch হলে আপডেট করুন)।
- `app/Http/Controllers/Api/RoleController.php`, `app/Http/Controllers/Api/PermissionController.php`: create/update সময় `tenant_id` সেট করে; নতুন কোন resource যোগ করলে একইভাবে `tenant_id` সেট করবেন।
- `database/seeders/TenantSeeder.php` + `database/seeders/DatabaseSeeder.php`: ডিফল্ট টেন্যান্ট তৈরি করে এবং প্রথম টেন্যান্টের সাথে admin user seed করে—ডেমো/লোকাল টেস্টের জন্য।

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
