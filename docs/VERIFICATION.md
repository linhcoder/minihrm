# Verification record

Local verification performed on 27 October 2026 (Asia/Bangkok).

| Check | Actual result |
| --- | --- |
| PHP 8.3 unit/service suite | 37 tests / 63 assertions passed |
| HTTP integration, SQLite | 153 checks passed |
| HTTP integration, MySQL 8.4.3 | Same 153 checks passed against an isolated loopback database |
| React TypeScript / Vite production build | Passed |
| Web authenticated-client tests | 4 passed (session restoration/rotation, revoked refresh, validation errors, invalid-login behavior) |
| Android debug APK | Built successfully |
| Android JVM tests | 4 tests passed, zero failures/errors |
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
