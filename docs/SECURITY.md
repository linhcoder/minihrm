# Security and privacy operations

## Enforced controls

- Opaque access tokens expire after 15 minutes; refresh tokens expire after 30 days, rotate once and revoke their previous access token. Only SHA-256 token hashes are stored server-side.
- Passwords use PHP's password hashing API. Verification/reset links expire in one hour and are single-use. Reset revokes active sessions.
- Tenant context comes from authenticated membership. Object queries always include company ownership. Tenant IDs in request bodies do not select context.
- Owner/admin/manager/employee roles have distinct grants. Payroll and billing require explicit permission or ownership. Platform admin access is isolated and audited.
- Critical SQL writes use tenant/object locks and uniqueness constraints. Parameterized query builders prevent interpolation of user input into SQL.
- Business validation rejects cross-tenant foreign references, out-of-branch manager objects, invalid dates and non-integer monetary values.
- Uploads accept detected JPEG/PNG/PDF only, with a 2 MB limit, bounded image dimensions, randomized private names and authenticated download. They are not served directly from a public directory. No SVG/HTML/script uploads are accepted.
- Exports enforce the same scoping as reads. CSV formula prefixes are escaped; XLSX cells are written as text and untrusted formulas are not evaluated during import.
- React escapes text by default. Production Nginx supplies content-type, frame and CSP headers. Authentication is bearer-based, not a cookie session, so CSRF tokens do not apply to these JSON endpoints. Keep the API same-origin; do not add permissive credentialed CORS.
- Emergency contacts use AES-256-GCM with a required 256-bit application key. Use encrypted VPS volumes and encrypted backups for other financial/contact records. HTTPS is required for release Android and production web.

## Secret management

Set a random 64-hex `HRM_ENCRYPTION_KEY`. Back it up separately from encrypted data. Do not rotate it without decrypt/re-encrypt migration. SMTP passwords, payment HMAC secrets, MySQL credentials and Firebase service-account files belong in private deployment secret storage. Never commit `.env`, private mail spools, uploads, device tokens or database copies.

Browser tokens live in session storage; an XSS would still threaten the session. Keep the CSP, avoid HTML injection, review dependencies and use short-lived access tokens. Android uses Keystore-backed AES-GCM, disables backups and stores local attendance only in the app sandbox. On logout, local pending attempts are removed to prevent accidental synchronization under a different account; synchronize before logout.

## Location and Wi-Fi

No background/continuous tracking and no biometrics are implemented. Location is collected only at the moment of attendance when the branch requires it. Approximate Android location is accepted as evidence but may require manual verification when accuracy is insufficient. Mock-location indicators are advisory and cannot eliminate spoofing. SSID/BSSID alone is never a trusted proof of presence; Wi-Fi attempts require manager verification and correction requests are available when Android restricts access.

## Data-subject requests

Employees can create `privacy-requests` of kind `access`, `export` or `anonymize`. Authorized company settings administrators review these requests. Approved access/exports create a private per-user export. Downloads enforce the original authenticated subject, not just possession of an ID. Approved anonymization deactivates the user and removes direct contact identifiers; necessary attendance/payroll/audit history is retained. Owner anonymization is rejected until ownership is transferred through an accountable administrative process.

Privacy exports and auth mail contain sensitive links/data: keep `writable` private, restrict operator access, delete exported files after delivery according to company policy, and exclude them from public backups. Anonymization does not promise complete erasure of payroll snapshots or legally retained evidence. A reviewer must approve the lawful scope and retention before fulfilling a request.

## Vietnam data-protection review checklist

Prepare a purpose inventory, employee notices, lawful-processing analysis, data-subject request procedure, processor agreements, access matrix, retention schedule, breach response and cross-border/service-provider assessment for professional review. Review geolocation necessity, consent/notice where applicable, and labor/accounting retention obligations. This repository does not assert legal certification.

## Incident response

Suspend the affected tenant from the platform portal, preserve audit evidence, revoke affected tokens, rotate compromised credentials and inspect cross-tenant access logs. Investigate before anonymizing/deleting evidence. Restore only from a tested, encrypted backup into an isolated environment before reopening access.
