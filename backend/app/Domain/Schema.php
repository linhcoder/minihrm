<?php
namespace App\Domain;
final class Schema
{
    public const RESOURCES = [
        'branches' => ['name'=>'text!', 'address'=>'text', 'latitude'=>'decimal', 'longitude'=>'decimal', 'radius'=>'int', 'timezone'=>'text', 'attendance_policy'=>'text', 'wifi_bssid'=>'text', 'active'=>'int'],
        'departments' => ['name'=>'text!', 'manager_id'=>'ref:employees'],
        'employees' => ['code'=>'text!', 'full_name'=>'text!', 'email'=>'email!', 'phone'=>'text', 'birth_date'=>'date', 'department_id'=>'ref:departments', 'branch_id'=>'ref:branches', 'user_id'=>'ref:users', 'avatar_file_id'=>'ref:files', 'job_title'=>'text', 'employment_type'=>'text', 'hire_date'=>'date!', 'status'=>'text', 'salary_type'=>'text', 'base_salary'=>'money', 'allowance'=>'money', 'emergency_contact'=>'secret', 'notes'=>'text'],
        'shifts' => ['name'=>'text!', 'kind'=>'text', 'start_time'=>'time!', 'end_time'=>'time!', 'break_minutes'=>'int', 'paid_break'=>'int', 'grace_minutes'=>'int', 'earliest_minutes'=>'int', 'latest_minutes'=>'int', 'overtime_eligible'=>'int', 'working_days'=>'json', 'effective_from'=>'date', 'effective_to'=>'date'],
        'schedules' => ['employee_id'=>'ref:employees!', 'shift_id'=>'ref:shifts!', 'branch_id'=>'ref:branches!', 'work_date'=>'date!', 'status'=>'text'],
        'attendance' => ['employee_id'=>'ref:employees!', 'schedule_id'=>'ref:schedules', 'kind'=>'text!', 'occurred_at'=>'text!', 'local_timestamp'=>'text', 'latitude'=>'decimal', 'longitude'=>'decimal', 'accuracy'=>'int', 'mock_location'=>'int', 'offline'=>'int', 'status'=>'text', 'idempotency_key'=>'text!', 'device_id'=>'text'],
        'timesheets' => ['employee_id'=>'ref:employees!', 'schedule_id'=>'ref:schedules!', 'work_date'=>'date!', 'check_in'=>'text', 'check_out'=>'text', 'worked_minutes'=>'int', 'late_minutes'=>'int', 'early_minutes'=>'int', 'overtime_minutes'=>'int', 'status'=>'text', 'review_status'=>'text', 'approved_by'=>'ref:users'],
        'attendance-corrections' => ['employee_id'=>'ref:employees!', 'timesheet_id'=>'ref:timesheets!', 'original_values'=>'json', 'new_values'=>'json!', 'reason'=>'text!', 'status'=>'text', 'approved_by'=>'ref:users'],
        'leave-types' => ['name'=>'text!', 'paid'=>'int', 'annual_days'=>'int', 'carryover_days'=>'int', 'effective_from'=>'date!'],
        'leave-requests' => ['employee_id'=>'ref:employees!', 'leave_type_id'=>'ref:leave-types!', 'start_date'=>'date!', 'end_date'=>'date!', 'half_day'=>'int', 'reason'=>'text!', 'days'=>'decimal', 'status'=>'text', 'approved_by'=>'ref:users'],
        'salary-advances' => ['employee_id'=>'ref:employees!', 'amount'=>'money!', 'request_date'=>'date!', 'reason'=>'text!', 'status'=>'text', 'payroll_period_id'=>'ref:payroll-periods', 'approved_by'=>'ref:users'],
        'salary-policies' => ['name'=>'text!', 'effective_from'=>'date!', 'standard_days'=>'int', 'standard_minutes'=>'int', 'overtime_multiplier_bps'=>'int', 'version'=>'int', 'components'=>'json'],
        'legal-rules' => ['name'=>'text!', 'effective_from'=>'date!', 'effective_to'=>'date', 'rules'=>'json!', 'verified'=>'int', 'reference'=>'text'],
        'payroll-periods' => ['name'=>'text!', 'start_date'=>'date!', 'end_date'=>'date!', 'branch_id'=>'ref:branches', 'department_id'=>'ref:departments', 'policy_id'=>'ref:salary-policies!', 'status'=>'text', 'total_net'=>'money', 'calculation_version'=>'int'],
        'payroll-adjustments' => ['employee_id'=>'ref:employees!', 'payroll_period_id'=>'ref:payroll-periods!', 'kind'=>'text!', 'amount'=>'money!', 'reason'=>'text!', 'status'=>'text', 'approved_by'=>'ref:users'],
        'payslips' => ['employee_id'=>'ref:employees!', 'payroll_period_id'=>'ref:payroll-periods!', 'gross'=>'money', 'deductions'=>'money', 'employer_cost'=>'money', 'statutory'=>'money', 'advances'=>'money', 'net'=>'money', 'inputs'=>'json', 'breakdown'=>'json', 'status'=>'text'],
        'shift-swaps' => ['employee_id'=>'ref:employees!', 'schedule_id'=>'ref:schedules!', 'target_schedule_id'=>'ref:schedules!', 'reason'=>'text!', 'status'=>'text'],
        'notifications' => ['user_id'=>'ref:users!', 'title'=>'text!', 'body'=>'text!', 'read_at'=>'text', 'event_key'=>'text!'],
        'files' => ['employee_id'=>'ref:employees', 'name'=>'text!', 'storage_key'=>'text!', 'mime'=>'text!', 'size'=>'int'],
        'privacy-requests' => ['user_id'=>'ref:users!', 'kind'=>'text!', 'reason'=>'text!', 'status'=>'text'],
        'holidays' => ['name'=>'text!', 'work_date'=>'date!'],
    ];
    public static function table(string $resource): string { return str_replace('-', '_', $resource); }
}
