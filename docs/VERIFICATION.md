# Verification record

Local verification performed on 27 October 2026 (Asia/Bangkok).

Latest change: [unlimited access and Vietnamese dates](UNLIMITED-ACCESS-AND-DATES.md). Regression checks confirm that missing/expired/Free subscriptions and former employee caps do not restrict operations, while tenant isolation and payroll authorization remain enforced. CSV/XLSX/PDF exports and Vietnamese Excel input were exercised. The updated APK was installed on the API 35 emulator; `27/10/2026 10:57:13` and `08:00:00 – 17:00:00` were observed in the UI. See `tests/results/android-vietnam-dates.png` and `android-date-unit.xml` for current evidence.

| Check | Actual result |
| --- | --- |
| PHP 8.3 unit/service suite | 43 tests / 80 assertions passed |
| HTTP integration, SQLite | 154 checks passed |
| HTTP integration, MySQL 8.4.3 | Same 154 checks passed against an isolated loopback database |
| React TypeScript / Vite production build | Passed |
| Web client and Vietnamese date tests | 10 passed (authentication, UTC+7 conversion, midnight, date-only values, invalid dates and nested audit presentation) |
| Android debug APK | Built successfully |
| Android JVM tests | 9 tests passed, zero failures/errors, including Vietnamese dates and offline payloads |
| Android lint | Passed with zero errors; dependency-version and SharedPreferences warnings remain |
| Browser visual/interaction QA | Not run: Browser runtime returned `No browser is available`, discovery returned an empty list |
| Android emulator runtime (API 35) | APK installed; real employee login, home data, schedule and own profile verified; stopping API queued an offline attempt, restoring API let WorkManager synchronize it as pending verification |
| Physical-device GPS/Wi-Fi and release signing | Not verified; requires the actual device/site and release credentials |
| Docker/Ubuntu VPS deploy | Configuration prepared; Docker/production hosting not executed in this environment |
| SMTP / FCM / actual payment provider | Adapters/fallbacks implemented; no live credentials supplied, so live delivery/settlement not claimed |
| Statutory payroll certification | Not implemented/verified; product labels results as non-statutory estimates |

The integration runner creates real companies, employees, schedules, events, timesheets, corrections, leave, payroll periods/slips, private files, invoices and payment records. It tests real registration, single-use email verification and onboarding, actual HTTP responses and binary XLSX/PDF/CSV output, privacy exports, private avatars and transactional XLSX imports. The same suite runs against SQLite and MySQL. MySQL composite foreign-key migrations were exercised locally.

Machine-readable API results are in `tests/results/api-sqlite.json` and `tests/results/api-mysql.json`. Android reports are generated under `android/app/build/reports/` and `android/app/build/test-results/`. The debug APK is under `android/app/build/outputs/apk/debug/`. PHPUnit reports are generated under `backend/build/logs/`.

Cross-tenant checks cover employee reads/updates, schedules, attendance, timesheets, payroll periods, payslips, report filters, private files, branches and privacy requests. Additional checks cover employee self-only reads, unpublished-payslip hiding, isolated Super Admin, ignored client tenant IDs, token rotation/replay/logout, expired-plan read access, duplicate attendance/import handling, approval ordering and locked payroll immutability.

Local testing also found and fixed an offline rejection bug: rejected events are now excluded from normalization and their timesheet is regenerated. A proposed employee audit permission was rejected by automatic review; the final history endpoint requires personnel administration permission, validates the scoped employee and redacts salary fields according to payroll permission. Employee audit access was not widened.

Before production use, complete the environment-dependent checks in `DEPLOYMENT.md`, visually verify web layouts in an available browser and run Android location/offline flows on physical devices. Passing local service tests is not a substitute for those checks.

The isolated emulator initially needed elevated runtime permission to write its Android feature-cache file. An early cold-start input caused a focused-window timeout under software rendering; repository/network/Keystore initialization was moved off the UI thread and the final APK was rebuilt, installed and rechecked. The final cold launch completed in 5,682 ms and restored its session/home without a new timeout dialog during the recheck. Emulator screenshots are stored in `tests/results/android-home-final.png` and `tests/results/android-offline.png`, with `android-runtime.json` recording the smoke checks. Physical-device startup performance remains a go-live check.

## User guidance update — 27/10/2026

- PHP: 48 tests, 90 assertions passed.
- Web: 18 tests passed; production TypeScript/Vite build passed.
- HTTP: 168 checks passed on each isolated SQLite and MySQL database.
- npm audit: zero vulnerabilities after updating Vitest and removing a circular local package dependency.
- Help center includes topic/search navigation, illustrated four-step articles, account-scoped reading progress and a role-specific introductory spotlight tour.
- In-app Browser discovery returned no available browser; visual browser QA remains unverified. DOM interaction tests cover search, image enlargement, read marking, first-visit dismissal persistence and tour replay/navigation.
