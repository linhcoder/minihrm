# Full feature access and Vietnamese date presentation

This change supersedes the original commercial-plan requirements in the supplied prompt.

All companies have the complete feature set, with no subscription expiry, trial deadline, commercial employee cap or upgrade requirement. This includes payroll, GPS attendance, leave, bulk scheduling, multiple branches, advanced reports and exports. A company can register and operate without any subscription row. Tenant isolation, active-account checks, branch restrictions, payroll permissions and locked-record controls remain enforced.

Company and platform navigation no longer contain plans, subscriptions, invoices or payment pages. The platform dashboard shows company/user/employee/attendance/queue statistics instead of subscription revenue. Expiry notifications are no longer generated. Legacy billing tables and records are preserved; historical access remains available to authorized operators. Creating renewals or plan purchases through the retired company billing actions returns HTTP 410. Historical plan rows, including expired and Free plans, never restrict access.

Previously queued expiry notices are marked retired without delivery when processed; their outbox records are retained.

Human-facing dates use **dd/MM/yyyy**, times use **HH:mm:ss**, and timestamps use **dd/MM/yyyy HH:mm:ss**, with timestamp display and date-filter boundaries interpreted in **Asia/Ho_Chi_Minh (UTC+7)**. This applies to web tables, detail/audit views, calendars, forms, Android screens and CSV/XLSX/PDF exports. Date-only values never shift to a different calendar day during formatting.

For deterministic calculation and integration, database values and API responses retain canonical ISO dates and UTC timestamps. Forms convert Vietnamese input to canonical values, and backend date validation also accepts Vietnamese dates directly. Attendance verification/correction timestamps accept Vietnamese local time and convert it to UTC. Excel employee imports accept dd/MM/yyyy as well as ISO dates. Business timezone calculations for shift matching and payroll remain intact.

No historical data migration or deletion is required. Rebuild/redeploy the web and API, update the Android APK, and use the existing migrations for clean installations. Technical safeguards such as upload limits, export batch sizes and request validation are independent of commercial plans.
