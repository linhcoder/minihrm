# Delivery ledger

The supplied specification ends mid-list in section 22 (`salary_policy`). No later phase definitions or reference mockup were supplied. Sections 1–22 are the implementation scope.

Implementation stages: foundation and migrations; tenant security and authentication; company operations; normalized attendance; reviewed timesheets and payroll; subscription and notification adapters; Vietnamese web portals; Android client; automated verification and deployment.

Status and actual verification evidence are maintained here as work proceeds. External email, push, payment, production hosting and legal compliance cannot be described as verified without credentials and a real environment.

## Implementation coverage

| Specification areas | Delivered implementation |
| --- | --- |
| 1–3: stack and repository | CI4/PHP backend, React/TS/Vite web, Kotlin Android, shared schema migrations/seeders, modular domain services, infrastructure/scripts/docs/tests |
| 4: UI/UX | Vietnamese responsive navy-sidebar portal, dashboard charts/cards, data tables, filters, Radix dialogs, validation, empty/error/loading/toast states |
| 5: platform portal | Real platform statistics, tenant creation/suspension/reactivation, configurable plans/limits, subscriptions, manual billing verification, audit, settings, health |
| 6: onboarding | Verified registration, transactional company/branch/shift/policy setup, business-type recommendations, timezone/working days, invitations |
| 7–9: company and people | Tenant/branch-scoped dashboard, employee CRUD/archive, invitations, XLSX import/export, personnel audit history, branches/departments, role/branch assignment |
| 10: scheduling | Shift templates/types, effective dates, overnight/DST windows, date-filtered calendar, bulk assignment/copy and conflict detection, approved swaps |
| 11: Android | Login/refresh/reset, Keystore tokens, Compose personal screens, policy-dependent GPS/Wi-Fi attempts, Room/WorkManager offline queue, own payslip/leave/advance/notification/profile screens |
| 12: attendance | Server timestamps, duplicate UUIDs, GPS radius, unverified Wi-Fi/offline evidence, multi-event normalization, missing/late/early/overtime calculations, corrections and approvals |
| 13: leave | Effective-dated configurable types, working-day/holiday counting, half-days, date conflict/balance checks, approval/rejection and notifications |
| 14–15: payroll | Integer/BCMath calculations, monthly/daily/hourly/part-time inputs, allowances/overtime/bonuses/advances/deductions/custom policy components, immutable snapshots, calculate/review/approve/lock/publish, XLSX/PDF, own-only published slips |
| 16: reports | Attendance/time/late/absence/overtime/leave/advance/payroll/grouped/usage reports and scoped XLSX/PDF/CSV exports |
| 17–18: security/privacy | Membership-derived tenancy, scoped object checks, manager branches, payroll permissions, token rotation/revocation, rate limit, private uploads, AES-GCM emergency contact, audit, retention, reviewed export/anonymization requests |
| 19: subscriptions | Configurable entitlements/limits, trial/renew/change/cancel, verified activation, expiry/grace/read-only behavior, preserved history |
| 20: notifications | Real in-app notices, durable SQL outbox with Redis dispatch/leases, SMTP delivery/retry, generic FCM server adapter and device registration, safe unconfigured fallback |
| 21–22: API/database | Versioned JSON API, generated OpenAPI, validation/pagination/filtering, uniqueness/idempotency, migrations/indexes/MySQL composite foreign keys and seed data |

## Explicit limits

The supplied prompt contained no later phase list and no reference mockup. Browser visual QA could not run because no browser was connected. Physical-device behavior, public VPS deployment, signed release distribution and credentialed external integrations are not asserted as verified. Firebase Android SDK activation is project-specific and remains optional until configured. Wi-Fi evidence is never accepted as secure presence by itself. Ambiguous DST fold selection follows PHP timezone resolution, documented in architecture. Statutory Vietnamese rules remain deliberately unverified; all payroll is labeled an estimate. See `VERIFICATION.md` for evidence and `DEPLOYMENT.md` for setup/runbook steps.
