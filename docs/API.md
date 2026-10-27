# API guide

The generated [OpenAPI 3.0 contract](openapi.json) is produced with `php backend/spark hrm:openapi`. All endpoints are under `/api/v1`, accept JSON and use `Authorization: Bearer <access_token>` unless listed as public. Dates are ISO `YYYY-MM-DD`, UTC timestamps use ISO 8601, and money is integer VND.

## Authentication and onboarding

```http
POST /api/v1/auth/register
{"full_name":"Nguyễn Minh Anh","email":"owner@example.com","password":"a-unique-12+-character-password"}

POST /api/v1/auth/verify
{"token":"single-use-email-token"}

POST /api/v1/auth/login
{"email":"owner@example.com","password":"a-unique-12+-character-password"}

POST /api/v1/auth/refresh
{"refresh_token":"previous-refresh-token"}

POST /api/v1/onboarding
{"name":"Công ty An Nhiên","business_type":"office","timezone":"Asia/Ho_Chi_Minh","working_days":[1,2,3,4,5,6],"branch_name":"Trụ sở","address":"TP. Hồ Chí Minh","start_time":"08:00","end_time":"17:00","payroll_day":1}
```

Login returns access/refresh tokens and expiry. `GET /auth/me` returns the authenticated user, resolved tenant and membership. Logout revokes the current session. `/auth/forgot` always returns a generic response; `/auth/reset` accepts a one-time token and a new 12+ character password. A verified new owner has no tenant until onboarding completes atomically. Onboarding creates a 14-day Business trial and neutral, configurable defaults.

## Company resources

`GET /schema` supplies field contracts. Resource lists return `{"data":[],"meta":{"total":0,"page":1,"limit":25}}`. Supported query parameters include `page`, `limit` (max 200), `search`, `sort`, `direction`, `from`, `to` and applicable employee/branch/department/status filters. Errors use `{"error":{"code":422,"message":"...","details":{...}}}`.

Create with POST, update editable resources with PATCH, and archive supported records with DELETE. Business records use dedicated workflow endpoints instead of unrestricted updates. IDs referenced in bodies must resolve within the caller's tenant and branch/employee scope. Never send `tenant_id` as an authorization selector.

```http
POST /api/v1/employees
{"code":"NV001","full_name":"Trần Hoàng Nam","email":"nam@example.com","hire_date":"2026-10-01","branch_id":1,"employment_type":"full_time","salary_type":"monthly","base_salary":8000000,"allowance":500000}

POST /api/v1/employees/1/invite
{}

POST /api/v1/schedules
{"employee_id":1,"branch_id":1,"shift_id":1,"work_date":"2026-10-27"}

POST /api/v1/schedules/bulk
{"rows":[{"employee_id":1,"branch_id":1,"shift_id":1,"work_date":"2026-10-28"}]}

POST /api/v1/schedules/bulk
{"copy_from":"2026-10-19"}
```

The copy action copies the source seven-day range to the following week, checks conflicts and commits atomically. Bulk scheduling and employee export/import require their configured plan entitlement.

## Attendance and timesheets

`GET /mobile-home` returns the current employee, today's/ongoing overnight shifts, policy-required branch information, recent attendance and leave types. Only accounts linked to an employee can use it.

```http
POST /api/v1/attendance/attempt
{"schedule_id":1,"kind":"check_in","idempotency_key":"e508fb6e-3780-4608-8c62-655362c00817","offline":0,"local_timestamp":"2026-10-27T01:00:00Z","device_id":"android-device","latitude":10.77,"longitude":106.69,"accuracy":12,"mock_location":0}

POST /api/v1/attendance/12/approve
{"verified_at":"2026-10-27T01:00:00Z","reason":"Đã kiểm tra bằng chứng offline"}

POST /api/v1/timesheets/aggregate
{"from":"2026-10-01","to":"2026-10-31"}

POST /api/v1/attendance-corrections
{"employee_id":1,"timesheet_id":4,"new_values":{"check_in":"2026-10-27T01:00:00Z","check_out":"2026-10-27T10:00:00Z"},"reason":"Quên giờ ra"}

POST /api/v1/attendance-corrections/3/approve
{}

POST /api/v1/timesheets/4/approve
{}
```

Attendance requests require a 16–80-character UUID-like key. Retries with the same key return the original event; changing employee/action conflicts. Offline local time is evidence only. A manager must supply a verified UTC occurrence time and reason. Incomplete/pending timesheets cannot be approved for payroll. Any correction impacting an already calculated payroll input is rejected; use a controlled later-period adjustment.

## Leave and advances

POST a leave request with `leave_type_id`, `start_date`, `end_date`, `half_day` and `reason`. Employee IDs are derived from membership for employee callers. Approval/rejection uses `/{id}/approve` or `/reject`. Configured working days/holidays determine requested days; paid entitlements/carryover are company-configured and checked at approval. Cross-year requests must be split.

Salary advances use `employee_id`, `amount`, `request_date`, `reason` and an optional `payroll_period_id`. An authorized administrator assigns the appropriate draft payroll period and approves the advance before calculation. An advance is deducted only in its assigned period. Requests without an assigned period are not silently deducted.

## Payroll

Create `/payroll-periods` with name, start/end and policy ID, optionally branch/department. Then POST to `/{id}/calculate`, `/review`, `/approve`, `/lock`, `/publish` in that order. `GET /payslips?payroll_period_id=<id>` returns detailed results; each row retains JSON `inputs` and `breakdown`. Employees only see their own published slips. See [PAYROLL.md](PAYROLL.md) for formulas and compliance boundaries.

## Files, import/export and privacy

`POST /files/upload` accepts `employee_id`, `name` and base64 `content`; downloads use `/files/{id}/download` with bearer authentication. XLSX import uses `POST /imports` with base64 `content`. Headers must match allowed employee schema names; a minimal template contains `code,full_name,email,hire_date,employment_type,branch_id,department_id`. Import is transactional, max 500 rows and 2 MB compressed/20 MB expanded. Blank optional values are omitted. Errors include the failing row.

`GET /exports/{resource-or-report}?format=xlsx|pdf|csv` returns a binary file with the same tenant/permission scope. Resource exports include up to 10,000 rows; larger exports require narrower filters and fail explicitly. Report data is available at `/reports/{report-name}`.

POST `/privacy-requests` with `kind=access|export|anonymize` and reason. Authorized review uses `/privacy-requests/{id}/approve|reject`. The original data subject downloads an approved access/export from `/privacy-requests/{id}/download`.

## Billing and platform administration

Company owners use `/subscriptions`, `/plans` and `/billing`. POST `/billing/renew`, `/change-plan` or `/cancel`. Payment requires server verification; clients cannot set `paid`. Super Admin routes begin with `/super`: dashboard, tenant lifecycle/subscription configuration, plans, invoices, payments, settings, audit and health. POST `/super/payments` accepts `invoice_id`, integer `amount` and a unique `reference` for a manually verified payment.

`PATCH /memberships` accepts `id`, `role`, `permissions` and `branch_ids`. Only the company owner may assign roles, and the owner's role cannot be changed through this endpoint. Permissions include `people,schedule,attendance,leave,reports,settings,invite,payroll`; billing stays owner-only.
