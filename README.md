# UaiLabs

A multi-tenant SaaS platform built with Laravel 10 for managing operational routines, support requests, and team collaboration across multiple companies.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Architecture Overview](#architecture-overview)
- [Role Hierarchy](#role-hierarchy)
- [Features](#features)
  - [Authentication & Security](#authentication--security)
  - [Dashboard](#dashboard)
  - [Operational Checklists](#operational-checklists)
  - [Support Requests](#support-requests)
  - [Activity Log](#activity-log)
  - [Module System](#module-system)
  - [Stock Control](#stock-control)
  - [Superadmin Panel](#superadmin-panel)
- [Module Permission System](#module-permission-system)
- [Database Migrations](#database-migrations)
- [Getting Started](#getting-started)

---

## Tech Stack

| Layer      | Technology                          |
|------------|--------------------------------------|
| Backend    | PHP 8.1+, Laravel 10                 |
| Frontend   | Blade, Bootstrap 5, Bootstrap Icons  |
| Assets     | Vite + Sass                          |
| Auth       | Laravel Breeze (session-based)       |
| Database   | MySQL                                |

---

## Architecture Overview

UaiLabs is a **multi-tenant** application where each tenant is a **Company**. All data models are scoped to `company_id` via a global Eloquent scope (`TenantScope`), ensuring complete data isolation between tenants.

```
Company
 └── Users (admin / manager / staff)
      └── Units (work locations)
           └── Activities → TaskOccurrences (checklist items)
 └── ModulePermissions (role-level module access)
 └── UserModulePermissions (individual overrides)
 └── SupportRequests
      └── SupportRequestNotes (threaded conversation)
```

---

## Role Hierarchy

| Role         | Scope                                      |
|--------------|--------------------------------------------|
| `superadmin` | Platform-wide. Manages all companies and users. No company binding. |
| `admin`      | Full access within their company.          |
| `manager`    | Can manage checklists and submit support requests. |
| `staff`      | Executes checklist tasks only.             |

---

## Features

### Authentication & Security

- Session-based authentication via Laravel Breeze
- Per-user password change accessible from the navbar
- `SecureHeaders` middleware adds `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, and `Permissions-Policy` to every response
- `BlockMissingUserAgent` middleware rejects requests with no User-Agent header
- HTTPS enforced automatically in production (`URL::forceScheme`)
- Minimum 12-character password policy enforced globally
- Rate limiting on authenticated routes (`throttle:web-auth`)
- `EnsureUserIsActive` and `EnsureCompanyIsActive` middleware guard every authenticated route

### Dashboard

- Displays a summary of the day's operational activity
- **Date filter bar**: single day or date range (with averages when a range is selected)
- Metrics cards: total tasks, completed, pending, completion rate
- Daily breakdown table showing results per day in range
- Collaborator performance table with role badges
- **Superadmin view**: adds a support request overview with 4 status-count cards (open, in progress, answered, done) and a table of the 5 most recent open requests

### Operational Checklists

Module key: `rotinas`

- Lists all task occurrences assigned to the user's company/units for the current day
- **Slide-to-confirm** gesture replaces a simple button, preventing accidental completions (pure CSS + vanilla JS, no libraries; threshold at 82% of track width)
- Once completed, the slide locks and a **"Reopen"** button appears below
- Reopening requires a justification text
- Full **history timeline** per task showing every complete/reopen action with the responsible user and timestamp
- **Admin/Manager**: dropdown nav gives access to **Categories** and **Activities** management (create, edit, delete)

### Support Requests

- Available to all roles with a company (`manager`, `admin`, `staff`)
- Create a request with a title, description, and priority level
- **Threaded notes**: both the requester and superadmin can add notes; own messages appear on the right (blue), others on the left (grey)
- **Face picker**: 5-emoji intensity selector (😡 → 😄) on notes and on the close form for feedback
- **Close request**: either side can close; once closed, no further notes are allowed and a locked banner is displayed
- Status tabs: `open`, `in_progress`, `answered`, `done`
- **Superadmin view**: full inbox with priority/status inline selectors, star toggle for important requests, and classification controls in a sidebar

### Activity Log

- Tracks all significant user actions: login, logout, task complete, task reopen, and custom events
- Filterable by user, action type, and date range
- Superadmin can filter across all companies; other roles see only their own company
- Access restricted to `manager` and above

### Module System

UaiLabs uses a **three-level module permission system** that controls which features each user can access:

1. **Platform level** — a module can be globally disabled in `ModuleRegistry`
2. **Role level** — a superadmin can enable/disable a module for an entire role within a company (e.g., disable `estoque` for `staff` in Company A)
3. **User level** — individual overrides take precedence over role-level settings

In the navbar, inaccessible modules are shown as a locked greyed-out label (`bi-lock-fill`) instead of a link.

### Stock Control

Module key: `estoque`

- Scaffold in place; access is guarded by the module permission system
- Under construction — full inventory management coming soon

### Superadmin Panel

Accessible only to the `superadmin` role at `/admin/*`.

| Section              | Capabilities                                                                 |
|----------------------|------------------------------------------------------------------------------|
| **Companies**        | Create, edit, activate/deactivate, delete companies                          |
| **Users**            | Create, edit, activate/deactivate users across all companies                 |
| **Module Permissions** | Toggle module access per role and per individual user for each company     |
| **Support Requests** | View all requests, set priority, change status, flag as important, add notes, close |

---

## Module Permission System

```
canAccess(user, moduleKey)
  │
  ├─ isSuperAdmin? → true (always full access)
  │
  ├─ UserModulePermission exists? → use that value (individual override)
  │
  ├─ ModulePermission exists for (company, role, module)? → use that value
  │
  └─ default → true (open access)
```

Permissions are managed via the **Empresas → Módulos** screen in the superadmin panel.

---

## Database Migrations

| Migration | Table |
|---|---|
| `000002` | `companies` |
| `000003` | `units` |
| `000004` | `user_units` |
| `000005` | `categories` |
| `000006` | `activities` |
| `000007` | `task_occurrences` |
| `000008` | `audit_logs` |
| `000010` | `users` — role, company_id columns |
| `000011` | `users` — active column |
| `000012` | `users` — username column |
| `000020` | `support_requests` |
| `000021` | `support_requests` — closed_at, closed_by, feedback |
| `000022` | `support_request_notes` |
| `000023` | `task_occurrence_logs` |
| `000024` | `module_permissions` |
| `000025` | `user_module_permissions` |

---

## Getting Started

### Requirements

- PHP 8.1+
- MySQL 8.0+
- Node.js 18+
- Composer

### Local setup

```bash
git clone https://github.com/jeffersonlv/UaiLabs.git
cd UaiLabs

composer install
npm install && npm run dev

cp .env.example .env
php artisan key:generate

# Configure DB_* in .env, then:
php artisan migrate --seed
php artisan storage:link
```

### Seeded accounts (development)

After `--seed`, the following accounts are available (see `UserSeeder`):

| Role       | Login via `username` field |
|------------|----------------------------|
| superadmin | configured in `UserSeeder` |
| admin      | configured in `UserSeeder` |
| manager    | configured in `UserSeeder` |
| staff      | configured in `UserSeeder` |

### Production deployment

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
```

Set the following in your production `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
```

---

## License

MIT
