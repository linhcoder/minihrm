# Payroll and compliance

## State machine

`draft → calculated → reviewed → approved → locked → published`

Calculation may be repeated in `draft` or `calculated`. Each calculation increments the version and stores employee policy, approved timesheets, leave minutes, adjustments, advances, policy version and the resulting breakdown. Review/approval/locking require an explicit payroll permission; ordinary managers do not receive it by default. Employee reads only return their own published payslips.

After a period is calculated, the system rejects corrections affecting consumed time without a controlled adjustment in a later draft period. After review, adjustments and advances associated with that period are immutable. Locked/published periods cannot be recalculated or directly edited. Historical input snapshots are retained.

## Integer arithmetic

Money is integer VND. BCMath handles intermediate products to prevent integer overflow; half-up division rounds once at each component boundary. No binary floating point is used for money. Daily/hourly/monthly/part-time salary policies use approved normal minutes, paid leave minutes and approved overtime minutes. Monthly normal pay is capped at the configured standard monthly minutes. Fixed allowances are added as configured, rather than automatically prorated. Missing/unapproved timesheets block calculation for scheduled dates.

An overtime policy multiplier is expressed in basis points (`10000 = 1.0`). The default `10000` is a neutral company configuration, **not a statutory overtime rate**. Standard days/minutes and paid/unpaid leave types must be reviewed by the owner.

Approved payroll adjustments accept `bonus`, `allowance` and `deduction`. Salary advances are assigned to a payroll period by authorized administrators. A custom policy `components` array supports integer fixed amounts or integer basis points of earned base salary:

```json
[
  {"code":"meal","kind":"earning","amount":300000},
  {"code":"equipment","kind":"deduction","amount":100000},
  {"code":"employer_benefit","kind":"employer","basis_points":100}
]
```

Policy components have separate earnings, employee deductions and employer-cost directions. These are company policy components, not verified statutory calculations. Create a new effective-dated policy/version instead of editing one already attached to payroll.

## Compliance gate

Every payroll breakdown is labeled `non_statutory_estimate`, with a Vietnamese warning in web, Android and PDF exports. Statutory deductions remain zero until legally reviewed modules are implemented and verified. `legal-rules` is a separate effective-dated configuration layer: its records cannot be marked verified by ordinary CRUD. Merely entering a legal configuration does not silently activate or certify statutory payroll.

Before production payroll use, obtain an accountable legal/payroll review of:

- Employee and employer insurance applicability, contribution bases, caps and rates by effective date.
- Personal income tax brackets, allowances, exemptions, residency and dependents by effective date.
- Minimum wage reference region and effective date.
- Overtime/night work/public holiday rates, eligibility, rounding and legal limits.
- Working-time calendars, holiday pay, paid leave, carryover and accrual policy.
- Proration for hires/terminations, monthly standard days and unpaid leave.
- Rounding and adjustment policy, statutory filings and payslip disclosures.
- Golden test cases approved by the reviewer, historical replays and effective-date transitions.

No unverified 2026 legal values are embedded in the product. The application must continue to display the estimate warning until a later verified statutory implementation replaces this gate.
