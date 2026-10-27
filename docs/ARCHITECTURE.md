# Architecture

Current behavior: all companies have full feature access without package limits or expiry. Human-facing date/time uses dd/MM/yyyy HH:mm:ss (UTC+7). See [current access/date policy](UNLIMITED-ACCESS-AND-DATES.md).

## Boundaries

`backend/app/Domain` contains authentication, tenant context, scoped storage, resource validation, an unlimited-access compatibility policy, scheduling, attendance normalization, approvals, payroll, billing, reports, files, privacy and notifications. Controllers translate versioned HTTP requests into these services. The application is a modular monolith; there are no microservices.

`Schema::RESOURCES` defines persistent resource contracts and drives API validation and OpenAPI generation. Specialized services own state transitions. Generic resource updates cannot change attendance events, timesheets, payslips, approval fields, calculated totals or payroll workflow state. `Store` centralizes tenant scoping and audit insertion.

Web navigation separates the platform portal (`/super`) from company operations. Server enforcement is authoritative; hiding navigation is only a convenience. TanStack Query handles server state. Zod/React Hook Form validate authentication, Radix supplies accessible modal behavior, and Recharts displays real attendance statistics.

The Android application uses Compose Material 3, a Hilt ViewModel, Retrofit, Room and WorkManager. Android Keystore AES-GCM protects token storage. The API URL is configured at build time. The app only requests location during a policy-dependent attendance action.

## Ownership

All company records contain `tenant_id`, including raw attendance, approved timesheets, payroll inputs, invoice/payment history, notifications, file metadata, memberships and audit logs. Users, authentication credentials, plans, system settings and platform audit are global. An authenticated membership supplies the tenant; client-provided tenant identifiers are ignored.

MySQL migrations add same-tenant composite foreign keys for resource relationships. SQLite development mode relies on service validation for these relationships. Manager queries are restricted to configured branches; employee queries are restricted to their linked employee/user. Super Admin has a separate context and cannot call operational company endpoints.

## Attendance flow

1. A schedule assigns an employee, branch, local work date and shift.
2. Server time is recorded for an online attempt. Client time never becomes approved time automatically.
3. GPS is checked against the assigned branch only when enabled. Approximate/inaccurate or mock-indicated readings are flagged. Wi-Fi/BSSID matches remain unverified evidence, not proof of presence.
4. Offline attempts retain the original UUID, local timestamp and device evidence in Room. WorkManager resends the same UUID. Server retries return the original event; a mismatched action conflicts.
5. Normalization produces daily timesheets. Rejected events are excluded. Pending events cannot become payable minutes.
6. Managers verify evidence, correct timestamps with an audit record, and approve timesheets.
7. Payroll consumes reviewed daily records and approved leave/adjustment/advance inputs, never raw phone events.

Overnight windows use a local date and IANA timezone, then convert boundaries to UTC. DST durations use elapsed UTC minutes. Ambiguous local wall-clock times follow PHP's timezone resolution; companies needing explicit fold selection should avoid scheduling those ambiguous minutes until a fold policy is agreed. Split shifts are separate non-overlapping assignments; rotating and part-time schedules use templates/assignments. Flexible shifts suppress late/early penalties while preserving working-time and review requirements.

## Transaction and concurrency model

MySQL tenant row locks serialize personnel changes, attendance attempts, schedule assignment, payroll calculation and approvals. Refresh tokens and invoice settlement lock their specific rows. Unique keys protect event UUIDs, employee codes, daily timesheets, payslips, payment references and notification event keys.

Notification enqueue and in-app insertion occur in the business transaction. Redis dispatch hints may survive a rolled-back SQL transaction; workers always verify SQL before delivery. SQL is the durable outbox, Redis supplies dispatch hints, worker leases and a 15-second dashboard cache. Failed delivery uses exponential retry up to five attempts; failures remain visible to platform health. Email/FCM delivery is at least once: a provider can accept a request before the worker observes success. Event identifiers and deterministic email Message-ID support provider-side deduplication where available.

## Persistence and retention

Employees are archived, not physically deleted. Approved timesheets and payroll snapshots remain auditable. Anonymization removes direct contact identifiers and deactivates accounts while retaining necessary financial/audit evidence. Location retention removes coordinates/device metadata after a configured window. Privacy exports are private files, and only the authenticated data subject can download an approved export.
