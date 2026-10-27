# Mini HRM

Current behavior: all companies have full feature access without package limits or expiry. Human-facing date/time uses dd/MM/yyyy HH:mm:ss (UTC+7). See [current access/date policy](docs/UNLIMITED-ACCESS-AND-DATES.md).

Vietnamese multi-tenant attendance and payroll application for small businesses. PHP 8.3 / CodeIgniter 4 API, React / TypeScript web portals, Kotlin / Compose Android client, MySQL 8 production storage, Redis dispatch/cache and a durable SQL notification outbox.

The supplied prompt is preserved in [docs/MASTER-PROMPT.txt](docs/MASTER-PROMPT.txt). It ends mid-list in section 22. Implementation and verification scope are recorded in [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md).

## Local development on this workspace

The workspace contains an ignored portable PHP runtime and installed dependencies. No production credentials are included.

```powershell
# From C:\CODE\miniHRM
.\.tools\php\php.exe backend\spark migrate
.\.tools\php\php.exe backend\spark db:seed DemoSeeder
# Terminal 1
.\.tools\php\php.exe -S 127.0.0.1:8080 -t backend/public backend/vendor/codeigniter4/framework/system/rewrite.php
# Terminal 2
npm.cmd run dev --prefix web
```

Open **http://127.0.0.1:5173**. The Vite development proxy forwards `/api` to the backend. SQLite is supported for isolated development and tests; production uses MySQL. New installations should create `backend/.env` from `backend/env` with `CI_ENVIRONMENT=development`, `database.default.DBDriver=SQLite3`, and `database.default.database=minihrm.sqlite`. Use a unique 64-character hex `HRM_ENCRYPTION_KEY` and set `HRM_WEB_URL`.

Development seed accounts, all using **`MiniHrm!2026Demo`**:

| Account | Role |
| --- | --- |
| `owner@minihrm.test` | Owner of An Nhiên |
| `other@minihrm.test` | Owner of a separate company |
| `employee@minihrm.test` | Employee with access to their own records |
| `super@minihrm.test` | Platform administrator; no company operational access |

The demo seeder is prohibited in production. It creates real database records. Unconfigured verification and invitation emails are stored in private `backend/writable/mail/`; open the link from there during development. Configure SMTP and run `php backend/spark hrm:mail` to deliver real mail.

## Clean installation

Requirements: PHP 8.3 with intl, mbstring, mysqli, SQLite3 (tests), bcmath, zip, gd, dom, fileinfo and curl; Composer 2; Node 22; JDK 17; Android SDK 35/build-tools 35.0.0.

```sh
composer install --working-dir=backend
npm ci --prefix web
php backend/spark migrate
php backend/spark db:seed PlatformSeeder
```

See [deployment instructions](docs/DEPLOYMENT.md), [API and workflow examples](docs/API.md), [architecture](docs/ARCHITECTURE.md), [security and privacy](docs/SECURITY.md), [payroll policy and compliance](docs/PAYROLL.md) and [verification evidence](docs/VERIFICATION.md).

## Verification

```powershell
.\.tools\php\php.exe scripts\test-php.php --no-coverage
node tests\api.integration.mjs
npm.cmd run build --prefix web
npm.cmd test --prefix web
powershell -ExecutionPolicy Bypass -File scripts\build-android.ps1 -Sdk C:\CODE\miniHRM\.tools\android-sdk
```

On Linux use `php scripts/test-php.php --no-coverage`, `PHP_BINARY=php node tests/api.integration.mjs`, and `cd android && ./gradlew assembleDebug testDebugUnitTest lintDebug`.

The API integration runner creates a disposable database and starts its own server on a random loopback port. It checks tenant boundaries, employee privacy, offline verification, approvals, payroll publication/locking, exports, full feature access without subscriptions, Vietnamese date presentation and token rotation. Set `HRM_TEST_MYSQL_DB` to a **disposable database ending in `_test`** to run the same suite against MySQL. Never point this at real company data.

Android debug APK: `android/app/build/outputs/apk/debug/app-debug.apk`. Its development API URL is `http://10.0.2.2:8080/api/v1/` for the Android emulator. A release build requires your HTTPS hostname in `android/app/build.gradle.kts` and your own signing configuration. Debug credentials and cleartext transport must not be used in production.

## Operational boundaries

Payroll is explicitly a **non-statutory estimate**. Effective-dated legal configuration exists, but verified Vietnamese tax/insurance/minimum-wage rules are not supplied. SMTP, Firebase, provider-specific payment credentials, VPS deployment, release signing and physical-device GPS/Wi-Fi validation require environment configuration. No external integration is represented as live merely because its adapter exists.
