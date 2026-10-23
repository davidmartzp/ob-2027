# Origami Bogotá — backend

REST API of the Origami Bogotá congress. It replaces `app-ob/` (Laravel 9) from `../angular-ob`
with the same catalogue of services, rebuilt on **PHP 8.5 + Slim 4 + Eloquent** (`illuminate/database`,
without Laravel), **MariaDB 10.11** and **Symfony Mailer**. Specs: `../openspec/changes/crear-backend-slim/`.

- **Production**: the hosting's Apache + PHP 8.5 and its SMTP server. No Docker.
- **Local**: Docker (PHP 8.5 + Apache, MariaDB 10.11). Email goes to a Mailtrap sandbox.

## Run it locally

```sh
cp .env.example .env              # first time; set MAILER_DSN to your Mailtrap sandbox
docker compose up -d --wait       # API → http://localhost:8080 (port in .env: API_HOST_PORT)
docker compose exec api composer install
docker compose exec api php bin/console migrate
docker compose exec api php bin/console seed              # rooms and activities
docker compose exec api php bin/console user:create you@example.com "Your name"
docker compose exec api vendor/bin/phpunit                # all tests (in-memory SQLite)
```

`php bin/console` lists the commands:

| Command | What it does |
|---|---|
| `migrate` / `rollback` | Apply / undo the last batch of migrations (`rollback` needs `--force` in production) |
| `seed` | Idempotent seeds (`database/seeds/`) |
| `user:create <email> <name>` · `user:password <email>` | CMS users; the password is asked without echo, never as an argument |
| `mail:send` | Delivers the email outbox (hosting cron, every minute) |
| `import:legacy <images-folder>` | After migrating a copy of production: checks the schema, reports broken references, copies images |

## Architecture (hexagonal)

```
src/Domain/          plain PHP: entities, enums, rules, repository interfaces, exceptions
src/Application/     one use case per action, plus ports (RateLimiter, PasswordHasher, MailQueue, ImageStorage, TransactionManager)
src/Infrastructure/  adapters: Http (controllers, middleware, ErrorHandler), Persistence (Eloquent models, repositories,
                     migrations), Mail, Storage, Console, Config, Clock, Security
config/routes.php    every route and its guard
config/container.php which adapter implements each port
```

Domain ← Application ← Infrastructure; `tests/Unit/ArchitectureTest.php` fails if the core imports a framework.
Code is in English; user-facing messages are in Spanish.

Errors always look like `{"error": {"code": "validation_failed", "message": "…", "fields": {…}}}`
(`not_found` 404, `unauthorized` 401, `forbidden` 403, `conflict` 409, `too_many_requests` 429,
`bad_request` 400, `enrolment_closed` 403 + `"logout": true`, `internal_error` 500 without details in production).

## Routes

Tokens go in `Authorization: Bearer <token>`: CMS tokens from `POST /auth/login`, attendee tokens from
`POST /attendees/verify-code` or `POST /auth/redeem-link`.

| Method | Path | Who | Handler |
|---|---|---|---|
| `GET` | `/health` | public | `HealthController::show` |
| `GET` | `/images/{file}` | public | `ImageController::show` |
| `POST` | `/auth/login` | public | `AuthController::login` |
| `POST` | `/auth/logout` | CMS | `AuthController::logout` |
| `GET` | `/auth/me` | CMS | `AuthController::me` |
| `POST` | `/auth/attendee-link` | public | `AuthController::requestAttendeeLink` |
| `POST` | `/auth/redeem-link` | public | `AuthController::redeemLink` |
| `POST` | `/attendees` | public | `AttendeeController::register` |
| `POST` | `/attendees/verify-code` | public | `AttendeeController::verifyCode` |
| `POST` | `/attendees/remind-code` | public | `AttendeeController::remindCode` |
| `GET` | `/attendees` | CMS | `AttendeeController::index` |
| `POST` | `/attendees/confirm` | CMS | `AttendeeController::confirm` |
| `POST` | `/attendees/codes` | CMS | `AttendeeController::assignCodes` |
| `POST` | `/attendees/codes/regenerate` | CMS | `AttendeeController::regenerateCodes` |
| `GET` | `/attendees/{id}` | CMS | `AttendeeController::show` |
| `PUT` | `/attendees/{id}` | CMS | `AttendeeController::update` |
| `DELETE` | `/attendees/{id}` | CMS | `AttendeeController::delete` |
| `GET` | `/workshops` | public | `WorkshopController::publicList` |
| `POST` | `/attendees/{id}/workshops` | attendee (self) | `WorkshopController::propose` |
| `GET` | `/attendees/{id}/workshops` | attendee (self) | `WorkshopController::listMine` |
| `PUT` | `/workshops/{id}` | CMS or owner | `WorkshopController::update` |
| `DELETE` | `/workshops/{id}` | CMS or owner | `WorkshopController::withdraw` |
| `POST` | `/workshops/{id}/image` | CMS or owner | `WorkshopController::uploadImage` |
| `GET` | `/cms/workshops` | CMS | `WorkshopController::cmsList` |
| `PATCH` | `/workshops/{id}` | CMS | `WorkshopController::patch` |
| `GET` | `/partners` | public | `PartnerController::index` |
| `POST` | `/partners` | CMS | `PartnerController::create` |
| `PUT` | `/partners/{id}` | CMS | `PartnerController::update` |
| `DELETE` | `/partners/{id}` | CMS | `PartnerController::deactivate` |
| `POST` | `/partners/{id}/logo` | CMS | `PartnerController::uploadLogo` |
| `GET` | `/workshops/{id}/partners` | public | `PartnerController::ofWorkshop` |
| `PUT` | `/workshops/{id}/partners` | CMS or owner | `PartnerController::replaceForWorkshop` |
| `GET` | `/rooms` | CMS | `ScheduleController::rooms` |
| `GET` | `/activities` | CMS | `ScheduleController::activities` |
| `GET` | `/events` | CMS | `ScheduleController::events` |
| `POST` | `/events` | CMS | `ScheduleController::createEvent` |
| `DELETE` | `/events/{id}` | CMS | `ScheduleController::deleteEvent` |
| `GET` | `/events/workshops` | CMS | `ScheduleController::workshopEvents` |
| `GET` | `/events/{id}/attendees` | CMS | `ScheduleController::eventAttendees` |
| `GET` | `/events/active` | public | `ScheduleController::activeEvents` |
| `GET` | `/events/schedule` | public | `ScheduleController::schedule` |
| `GET` | `/attendees/{id}/events` | attendee (self) | `ScheduleController::myEvents` |
| `POST` | `/attendees/{id}/events` | attendee (self) | `ScheduleController::enrol` |
| `DELETE` | `/attendees/{id}/events/{event}` | attendee (self) | `ScheduleController::cancel` |
| `GET` | `/emails/log` | CMS | `EmailController::log` |
| `GET` | `/emails/{type}/recipients` | CMS | `EmailController::recipients` |
| `POST` | `/emails/{type}` | CMS | `EmailController::send` |

Legacy → new route map: `design.md` D3.

## Deploy on the hosting (no Docker)

1. Check the hosting runs **PHP 8.5** (dependencies are resolved for it).
2. Locally: `docker compose exec api composer install --no-dev --optimize-autoloader`.
3. Upload the project **with `vendor/`**, without `.env`, `tests/`, `docker/` and `compose.yaml`.
   Point the web root to `public/`; if the hosting cannot, the root `.htaccess` exposes only `public/`.
   If the API lives in a subfolder, set `APP_BASE_PATH` (e.g. `/api`) and include it in `APP_URL`.
4. Create `.env` on the server from `.env.example`: `APP_ENV=production`, `APP_DEBUG=false`, database,
   `MAILER_DSN=smtps://user:password@host51.latinoamericahosting.com:465` (production refuses mail without TLS),
   `SITE_URL`, `CORS_ORIGINS`, congress dates and turns.
5. `php bin/console migrate`, `php bin/console seed`, `php bin/console user:create …`.
6. Cron every minute: `php /path/to/backend/bin/console mail:send`.
7. Make `storage/` writable by PHP (uploaded images live in `storage/images/`).

## Moving the production data

1. Dump production (`mysqldump --default-character-set=utf8mb4 …`) and keep it **out of the repo** (personal data).
2. Load it into a **copy** database, then `php bin/console migrate`: the first migration renames
   `assistants` → `attendees`, `id_assistant` → `id_attendee` and drops unused tables.
3. `php bin/console import:legacy <folder with app-ob/storage/app/public/images>`.
4. Compare row counts, then point the site and the CMS to the new API.

Rehearsed with the real dump (2026-10-23): 169 attendees, 69 workshops, 137 events, 978 enrolments,
68 images; same counts after migrating.
