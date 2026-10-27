<?php
namespace App\Domain;
final class Reports
{
    public function __construct(private Store $s) {}
    public function run(string $kind,array $filters): array
    {
        $this->s->ctx->require('reports');$r=match($kind) {
            'daily-attendance','monthly-timesheets','late-arrivals','absences','overtime'=>'timesheets','leave-balances'=>'leave-requests',
            'payroll-summary','department-payroll','branch-payroll'=>'payslips','salary-advances'=>'salary-advances','subscription-usage'=>'subscriptions',default=>throw new Problem(404,'Báo cáo không tồn tại.')
        };
        if (in_array($r,['payslips','salary-advances'])) $this->s->ctx->require('payroll');
        if (in_array($kind,['department-payroll','branch-payroll'])) (new Subscriptions($this->s))->writable('advanced_reports');
        if ($kind==='subscription-usage') return ['data'=>[['employees'=>$this->s->query('employees')->where('status','active')->countAllResults(),'attendance_events'=>$this->s->query('attendance')->countAllResults(),'plan'=>(new Subscriptions($this->s))->current()['plan']['name']]]];
        $q=$this->s->query($r);
        if ($kind==='late-arrivals') $q->where('late_minutes >',0);
        if ($kind==='absences') $q->where('status','absent');
        if ($kind==='overtime') $q->where('overtime_minutes >',0);
        foreach(['employee_id','status'] as $k) if (!empty($filters[$k])) $q->where($k,$filters[$k]);
        $date=$r==='timesheets'?'work_date':($r==='leave-requests'?'start_date':($r==='salary-advances'?'request_date':'created_at'));
        if (!empty($filters['from'])) $q->where($date.' >=',$filters['from']);if (!empty($filters['to'])) $q->where($date.' <=',$filters['to'].($date==='created_at'?'T23:59:59Z':''));
        if (!empty($filters['branch_id'])||!empty($filters['department_id'])) {
            $e=$this->s->query('employees')->select('id');foreach(['branch_id','department_id'] as $k) if (!empty($filters[$k])) $e->where($k,$filters[$k]);$ids=array_column($e->get()->getResultArray(),'id');$q->whereIn('employee_id',$ids?:[-1]);
        }
        $rows=$q->limit(10000)->get()->getResultArray();
        if ($kind==='leave-balances') {
            $out=[];$types=$this->s->query('leave-types')->get()->getResultArray();$employees=$this->s->query('employees')->get()->getResultArray();
            foreach ($employees as $e) foreach($types as $type) {
                $used=0;foreach($rows as $l) if ($l['employee_id']===$e['id']&&$l['leave_type_id']===$type['id']&&$l['status']==='approved') $used+=(float)$l['days'];
                $out[]=['employee'=>$e['full_name'],'leave_type'=>$type['name'],'entitlement'=>(int)$type['annual_days'],'carryover'=>(int)$type['carryover_days'],'used'=>$used,'remaining'=>(int)$type['annual_days']+(int)$type['carryover_days']-$used];
            }$rows=$out;
        }
        if (in_array($kind,['department-payroll','branch-payroll'])) {
            $groups=[];$field=$kind==='department-payroll'?'department_id':'branch_id';
            foreach($rows as $row) { $e=$this->s->find('employees',(int)$row['employee_id']);$key=$e[$field]??0;$groups[$key]??=['group_id'=>$key,'gross'=>0,'net'=>0,'employees'=>0];$groups[$key]['gross']+=(int)$row['gross'];$groups[$key]['net']+=(int)$row['net'];$groups[$key]['employees']++; }$rows=array_values($groups);
        }
        foreach($rows as &$row) unset($row['inputs']);
        $this->s->audit('report',$kind,0,null,['rows'=>count($rows)]);return ['data'=>$rows,'meta'=>['total'=>count($rows),'maximum'=>10000]];
    }
}
