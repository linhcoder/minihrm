# Deployment and operations

## Ubuntu VPS prerequisites

Use a supported Ubuntu release, DNS pointing to the VPS, HTTPS terminated by host Nginx, Docker Engine/Compose, restricted SSH and an off-host encrypted backup destination. The container stack exposes only `127.0.0.1:8088`; MySQL, Redis and PHP-FPM have no host-published ports. MySQL 8.0 is used in Compose/CI. Local verification may also use MySQL 8.4.

Copy `.env.example` to `.env`. Replace all passwords, the public hostname and `HRM_ENCRYPTION_KEY`. Generate independent values, for example `openssl rand -hex 32`. Set the same MySQL password in `MYSQL_PASSWORD` and `database.default.password`, and the same Redis password in `REDIS_PASSWORD` and `HRM_REDIS_PASSWORD`. Keep `.env` readable only by the deploy operator. `backend/.env` is a local development file and must never be copied into the production image.

```sh
docker compose build
docker compose up -d mysql redis php
docker compose exec php php spark migrate
docker compose exec php php spark db:seed PlatformSeeder
# Pass credentials from private deployment environment, not command-line literals.
docker compose exec -e HRM_ADMIN_EMAIL -e HRM_ADMIN_PASSWORD php php spark hrm:admin
docker compose up -d web worker
curl --fail http://127.0.0.1:8088/api/v1/health
```

The platform admin password must contain at least 16 characters. Do not run `DemoSeeder` on production. The application rejects that operation.

## HTTPS reverse proxy

Configure host Nginx to proxy your domain to `http://127.0.0.1:8088`, redirect HTTP to HTTPS and install a valid certificate using your standard ACME process. Forward `Host`, `X-Real-IP`, `X-Forwarded-For` and `X-Forwarded-Proto`. Only trust those proxy addresses in CodeIgniter's proxy configuration. Add HSTS after verifying certificate renewal. Set `app.baseURL` and `HRM_WEB_URL` to the public HTTPS origin. Retain the application's same-origin routing and CSP.

For a native installation instead of containers, install PHP 8.3-FPM and the extensions listed in README, MySQL 8 and Redis; install Composer dependencies; build `web`; serve `web/dist` from Nginx and route only `/api/` to `backend/public/index.php`. Never make the repository root, `.env` or `writable` publicly readable. Run FPM/CLI workers as a dedicated unprivileged service account with write access only to `writable`.

## Scheduled work

The container worker runs queued notification delivery and auth-mail delivery every 15 seconds. Add a daily host cron entry:

```cron
15 0 * * * cd /srv/minihrm && docker compose exec -T php php spark hrm:daily >> /var/log/minihrm-daily.log 2>&1
```

The daily task sends idempotent expiry/missing-checkout notices, transitions expired subscriptions to read-only, and removes expired location/device evidence. Run it in UTC. Company business dates are interpreted using their saved timezone. Configure log rotation and monitor platform health for pending jobs and failures. Failed jobs remain in the outbox after five attempts; fix the provider configuration and reset `attempts`/`available_at` through an audited operator procedure before retrying.

## Email

Configure `email.protocol=smtp`, SMTP host/user/password/port/TLS, from address and name. Queue delivery only claims success after the mailer accepts delivery. Auth links are one-hour single-use credentials in the private spool. Test registration, email verification, invitation/reset and leave notification against a controlled mailbox before admitting production users. Configure SPF/DKIM/DMARC at the sending provider. Development without SMTP uses private spools and in-app notifications.

## Android push

Firebase is optional until configured. Store a service-account JSON file outside the public root and mount it read-only; set `HRM_FCM_SERVICE_ACCOUNT` to that private path. The backend exchanges a signed service-account assertion for an OAuth token and sends FCM HTTP v1 messages. Register Android device tokens at `POST /api/v1/push-device` after integrating the Firebase Android SDK and your own `google-services.json`. The repository does not contain that project-specific file or claim an active Firebase project. The unconfigured app uses its real in-app notifications screen. Push payloads contain a generic notice and event hash, never payroll details or location.

## Payment provider

Manual payment verification is available only to platform administrators and creates an audit entry. The generic signed adapter accepts a **server-to-server** settled notification with an HMAC-SHA256 signature in `X-Signature`, computed over the exact JSON body using `HRM_PAYMENT_WEBHOOK_SECRET` (32+ characters):

```json
{"invoice_id":12,"amount":199000,"status":"settled","reference":"provider-transaction-id","timestamp":1793060000}
```

Timestamps must be within five minutes, amounts must match the issued invoice, and provider references are unique. A browser callback never settles an invoice. Adapt the `PaymentProvider` interface to the actual provider's signing/reconciliation contract and test against its sandbox before enabling public billing. Do not send arbitrary client callbacks to this trusted adapter. A paid-plan change remains pending until the payment is verified; Free changes are applied server-side after count-limit validation.

## Android release

Set the HTTPS API origin in `android/app/build.gradle.kts`. Configure your own signing keystore through private CI secrets and run `./gradlew assembleRelease`. Release builds disallow cleartext. Test on physical devices for precise/approximate/denied location, disabled GPS, Android Wi-Fi restrictions, offline process death/retry, duplicate taps, token expiration, account switching and own-payslip access. No release signing keys are included.

## Backup, restore and upgrades

Run `scripts/backup.sh` with `BACKUP_DIR` outside all public directories, then encrypt/copy the artifacts off-host. Back up database, private writable files and the encryption key through separate protected channels. A backup is not considered verified until restored into a disposable environment and checked against payroll totals and a sample document.

Before upgrades, take a consistent backup, enter maintenance mode through platform settings, stop workers, build versioned images, run migrations and smoke-test login/tenancy/payroll reads. Resume workers and clear maintenance mode after checks pass. Schema rollback is intentionally not an automatic destructive `down()`; restore a reviewed snapshot or deploy a reviewed forward repair. Preserve financial history.

## Go-live checks requiring the actual environment

Confirm HTTPS/certificate renewal, SMTP delivery, signing/provider credentials, Redis connectivity, MySQL restore, Android device behavior, configured leave/payroll policy, professional statutory/privacy review and restricted operator access. The repository's local build/tests do not constitute a completed VPS deployment or legal certification.
