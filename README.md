# SonetSys

A self-hostable community platform — social feed, groups, chat, stories, and a wallet —
built as a **modular monolith** on Laravel 12 with a decoupled Vue 3 + TypeScript SPA.

The point of this repo is the architecture: fourteen feature modules that stay genuinely
independent, talking to each other only through published contracts, so a module can be read,
tested, or removed on its own.

---

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2 (`strict_types` throughout) |
| Database | PostgreSQL 16 |
| Cache / queue | Redis, database-backed queue |
| Auth | Laravel Sanctum (bearer tokens) |
| Real-time | Laravel Echo + Pusher |
| Frontend | Vue 3, TypeScript, Tailwind CSS v4, Pinia, Vue Router, Leaflet |
| Tooling | Vite, vue-tsc, ESLint + oxlint, Prettier, PHPUnit |

---

## Architecture

### Four layers, per module

Every module and every core concern is sliced the same way:

```
Feed/
├── Domain/            # models, enums, domain events — no framework decisions
├── Application/       # services, DTOs, and the Contracts this module depends on
├── Infrastructure/    # Eloquent repositories implementing those contracts
├── Http/              # controllers, form requests, API resources
└── routes/api.php     # the module's own routes
```

Nothing lives in `app/Models`. Models belong to the module that owns them, under
`Domain/Models`, and factories resolve by class basename instead of the default namespace
convention.

### Modules

`Feed` · `Chat` · `Group` · `Story` · `Follow` · `Block` · `Search` · `Notification` ·
`Report` · `Wallet` · `Subscription` · `Advertising` · `Song` · `Menu`

Over a shared core of `Auth`, `Settings`, and `Storage`.

### Modules depend on contracts, not on each other

Twenty-one repository interfaces are declared in the `Application/Contracts` of the module that
*needs* them, and bound to Eloquent implementations by the module that *owns* the data. A module
never imports another module's concrete classes.

The clearest case is the feed needing to know whether a user may post into a group:

```php
// app/Modules/Feed/Application/Contracts/GroupAccessCheckerInterface.php
// Declared by Feed — this is the shape of the answer Feed needs.
interface GroupAccessCheckerInterface { /* ... */ }

// app/Modules/Group/GroupServiceProvider.php
// Implemented and bound by Group — Feed never learns Group exists.
$this->app->bind(GroupAccessCheckerInterface::class, GroupAccessChecker::class);
```

Delete the Group module and Feed still compiles; it just needs a different binding.

### Caching that degrades instead of breaking

`TaggableCache` wraps `Cache::tags()` so callers get real tag-based invalidation when Redis is
the store, and fall back to plain TTL expiry when it isn't — rather than throwing on a store
that has no tag support. Each module owns its own cache keys and invalidation rules
(`FeedCache`, `GroupCache`).

### Events decouple producers from notifications

Notifications are not called from the code that causes them. A module raises a domain event about
its own business — `ContentLiked`, `CommentPosted`, `PostShared`, `UserMentioned`, `UserFollowed` —
and the Notification module subscribes to all five in its own service provider:

```php
// app/Modules/Notification/NotificationServiceProvider.php
Event::listen(ContentLiked::class,  SendLikeNotification::class);
Event::listen(CommentPosted::class, SendCommentNotification::class);
Event::listen(PostShared::class,    SendShareNotification::class);
Event::listen(UserFollowed::class,  SendFollowNotification::class);
Event::listen(UserMentioned::class, SendMentionNotification::class);
```

Feed and Follow contain **zero** references to the Notification module. Adding a new notification
means adding a listener, never editing the feature that triggers it.

Only two events reach the browser: `NotificationCreated` and `MessageSent`, both
`ShouldBroadcastNow` on a private per-user channel. Broadcast channel definitions are registered
per module rather than centrally.

### Pluggable storage

Uploads go through a `StorageService` backed by a `StorageDriver` enum — `local` or `s3` —
selected at runtime from admin settings rather than being fixed at deploy time.

### Access control

Three roles (`admin`, `moderator`, `user`) and fifteen granular permissions, both as string-backed
enums: `users.manage`, `roles.manage`, `posts.delete.any`, `comments.delete.any`,
`groups.manage.any`, `reports.review`, `ads.review`, `wallet.manage`, `storage.manage`,
`settings.manage`, and more.

Login and password reset get **separate** rate-limit buckets (5/min and 3/min, keyed by IP), so
flooding the public password-reset endpoint cannot exhaust the budget that legitimate users need
to log in.

---

## API

All routes are versioned under `/api/v1` and return a single envelope:

```json
{ "success": true, "data": {}, "error": null, "meta": {} }
```

Authentication is Sanctum bearer tokens — the SPA is a separate origin, not a Blade app.

---

## Getting started

**Requirements:** PHP 8.2+, Composer, Node 20+, Docker (or your own PostgreSQL 16 + Redis 7).

```bash
# 1. Database and cache
docker compose up -d          # postgres:16-alpine + redis:7-alpine, both health-checked

# 2. Backend
composer setup                # install, .env, key:generate, migrate, npm install, build
php artisan db:seed           # roles, permissions, and demo data

# 3. Frontend
cd frontend
cp .env.example .env          # point VITE_API_BASE_URL at http://localhost:8000/api/v1
npm install
npm run dev
```

Run the whole backend stack — server, queue worker, log tail, and Vite — with one command:

```bash
composer dev
```

---

## Testing

```bash
composer test                 # clears config, then runs the suite
```

Feature tests are organised to mirror the source tree (`tests/Feature/Core`,
`tests/Feature/Modules`, `tests/Feature/Admin`).

Frontend checks:

```bash
cd frontend
npm run type-check            # vue-tsc --build
npm run lint                  # oxlint + eslint
```

---

## Project layout

```
app/
├── Core/          # Auth, Settings, Storage, Support — shared by every module
├── Modules/       # 14 feature modules, same four-layer shape each
├── Http/          # global middleware only
└── Providers/
frontend/src/
├── modules/       # mirrors the backend modules; each owns its TypeScript
│                  # interfaces matching that module's API resources
├── shared/
├── app/
└── i18n/
database/
├── migrations/    # 53 migrations
└── seeders/       # RoleSeeder, PermissionSeeder, DemoDataSeeder
```

---

## Notes

- **Rate limiting is keyed by IP only.** There is no reverse proxy or CDN in front of this
  today, so `$request->ip()` is trustworthy. If one is added, `trustProxies()` must be
  configured in `bootstrap/app.php` — otherwise every request collapses onto the proxy's IP,
  or becomes spoofable through `X-Forwarded-For`.
- **Single-tenant.** An earlier iteration carried multi-tenancy; it was removed deliberately to
  keep the domain model simple. One deployment serves one community.

---

## License

Released under the [MIT License](LICENSE).
