# University Asset Management System

Final Year Project — Somali National University. Tracks university-owned
assets through their full lifecycle: registration, allocation, transfer,
maintenance, auditing, requisition, and disposal, with role-based access
for four user types.

## Tech stack

- **Backend:** PHP (PDO + prepared statements, no framework)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, vanilla JavaScript
- **Architecture:** MVC-style separation — `/config` and `/includes` hold
  data/business logic, `/modules/**` act as controllers (fetch data, then
  include a view partial), `/static` holds presentation assets only.

## Folder structure

```
/config                 Database connection (config/database.php) and app config (config/config.php)
/includes
  /layout                Shared header/sidebar/footer/forbidden-page partials
  auth.php                Session guards: requireLogin(), requireRole(), hasRole()
  functions.php             Sanitization, CSRF, flash messages, activity/login logging, status badges
  bootstrap.php               Single include point used by every module entry file
/modules
  /auth                        login.php, logout.php
  /dashboard                    Role-aware summary dashboard
  /assets                        Register / edit / view (full history) / list & filter assets
  /assigned                       Allocate assets to departments/custodians, mark returned/repair
  /transfers                       Transfer assets between departments
  /maintenance                      Report & manage maintenance tickets
  /audits                           Physical audit records (found / missing / damaged)
  /requisitions                      Department requests → Officer/Admin review → approve/reject/issue
  /disposals                          Officer/Admin requests disposal → Top Management approves/rejects
  /users /departments /categories /locations   Admin-only master data CRUD
  /reports                            Assets by dept/category/status, maintenance cost, disposal report, CSV export
  /profile                             Any logged-in user: edit own profile, change password
  /settings                            Admin-only: general settings, SMTP, backup/restore, activity & login logs, system info
/static
  /css/style.css        Theme: dark green sidebar, green/orange/white/gray accents
  /js/main.js             Sidebar toggle, modals, confirmations, table search/sort/tabs
  /js/validation.js         Client-side form validation (UX layer only — server always re-validates)
/uploads/logos          Uploaded university logo images
/storage/backups         (reserved for local backup copies if you choose to keep any)
/database/schema.sql      Full schema + seed data
index.php                 Entry point — redirects to login or dashboard
```

## Database schema

The brief specified 10 tables but only listed 9, and two things were
referenced with no backing table (`assets.location_id` and "audit"
status/results). Per the agreed scope this schema uses **15 tables**:

The original 9 — `roles`, `users`, `departments`, `categories`, `assets`,
`asset_assigned`, `asset_transfers`, `asset_maintenance`, `requisitions` —
plus:

- `locations` — physical building/room an asset lives in (closes the
  `assets.location_id` gap)
- `asset_audits` — physical verification records (found / missing / damaged)
- `asset_disposals` — disposal **request → Top Management approval**
  workflow (an asset's status only flips to `disposed` once approved)
- `settings`, `activity_logs`, `login_logs` — back the admin Settings
  module (general config, SMTP, backup/restore, audit trail, login history)

Notable design choices:

- `asset_assigned` keeps the brief's `assigned_to → departments` FK and
  adds a nullable `assigned_user_id → users` so allocation history can be
  queried per department *and* per specific custodian.
- `assets.status` is **derived, not hand-edited**: `recomputeAssetStatus()`
  in `includes/functions.php` sets it to `disposed` if there's an approved
  disposal, `under_repair` if there's an open maintenance ticket, else
  `active`. It's called after every maintenance/disposal state change.
- Every table has `created_at` / `updated_at`, sensible `ON DELETE` /
  `ON UPDATE` rules (`CASCADE` for history tied to an asset, `RESTRICT` for
  users who authored a record, `SET NULL` for optional references).

## Setup

1. **Create the database.** Import the schema (creates the DB, all tables,
   and seed data in one go):
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. **Configure the connection.** Edit `config/database.php` and set
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` to match your MySQL setup.
3. **Set the base URL.** If you're not serving the app from `/asm`, update
   `APP_URL` in `config/config.php`.
4. **Permissions.** Make sure the web server can write to `/uploads/logos`
   (logo uploads) and `/storage` (error log).
5. **Serve it.** Point your web server (Apache/XAMPP/PHP built-in server)
   at the project root, e.g.:
   ```bash
   php -S localhost:8000
   ```
   then visit `http://localhost:8000/modules/auth/login.php`
   (or just `http://localhost:8000/`, which redirects there).

## Default login credentials (demo/testing)

Every seeded account uses the same password: **`Password123!`**. Log in
with either the email or the username shown below.

| Role            | Email                          | Username          |
|-----------------|---------------------------------|-------------------|
| Admin           | admin@sun.edu.so                | admin             |
| Asset Officer   | asset.officer1@sun.edu.so        | asset.officer1    |
| Asset Officer   | asset.officer2@sun.edu.so         | asset.officer2    |
| Department Head | head.ict@sun.edu.so (ICT)          | head.ict          |
| Department Head | head.library@sun.edu.so (Library)   | head.library      |
| Department Head | head.finance@sun.edu.so (Finance)    | head.finance      |
| Top Management  | topmanagement@sun.edu.so              | topmanagement     |

Change these before any real deployment — this password is for demo
purposes only.

## Role permissions summary

| Module               | Admin | Asset Officer | Department Head           | Top Management |
|-----------------------|-------|----------------|-----------------------------|------------------|
| Assets                | Full  | Full           | View own department only    | View all          |
| Allocations/Transfers  | Full  | Full           | View own department only    | View all           |
| Maintenance             | Full  | Full           | View + report issues only    | View all            |
| Audits                    | Full  | Full           | View own department only      | View all             |
| Requisitions                | Review | Review       | Submit (own department)        | View all              |
| Disposals                     | Full   | Request only | View own department only        | Approve / reject        |
| Users/Departments/Categories/Locations | Full | — | — | — |
| Reports                          | Full   | Full        | Own department scope              | Full (view-only)          |
| Settings                           | Full   | —          | —                                    | —                            |

Every page enforces its role check **server-side** in the PHP file itself
(`requireRole([...])` in `includes/auth.php`) — hiding a sidebar link is
never the only protection.

## Security notes

- All queries use PDO prepared statements with bound parameters.
- Passwords are hashed with `password_hash()` / verified with
  `password_verify()`.
- All output is escaped with `htmlspecialchars()` via the `e()` helper.
- Every state-changing form includes a CSRF token, checked with
  `requireCsrf()` before the request is processed.
- Server-side validation runs on every form handler regardless of the
  client-side JavaScript checks in `static/js/validation.js`.
