<?php
namespace App\Domain;
final class PayrollEngine
{
    /** All money is integer VND. Half-up division, no binary floating point. */
    public static function divide(int $n,int $d): int { if ($d<1||$n<0) throw new Problem(422,'Thông số tính lương không hợp lệ.'); return intdiv($n+intdiv($d,2),$d); }
    private static function productDivide(array $factors,int $denominator): int
    {
        if ($denominator<1) throw new Problem(422,'Mẫu số không hợp lệ.');$product='1';
        foreach($factors as $factor) { if($factor<0)throw new Problem(422,'Hệ số không được âm.');$product=bcmul($product,(string)$factor,0); }
        $rounded=bcdiv(bcadd($product,(string)intdiv($denominator,2),0),(string)$denominator,0);
        if(bccomp($rounded,'9000000000000000',0)>0)throw new Problem(422,'Số tiền vượt giới hạn xử lý.');return (int)$rounded;
    }
    public static function calculate(array $employee,array $sheets,array $policy,array $adjustments=[],int $advances=0,int $paidLeaveMinutes=0): array
    {
        $base=(int)$employee['base_salary']; $allowance=(int)($employee['allowance']??0);
        $days=(int)$policy['standard_days']; $dayMinutes=(int)$policy['standard_minutes'];
        if ($days<1||$days>31||$dayMinutes<1||$dayMinutes>1440||$base>999999999999||$base<0) throw new Problem(422,'Chính sách lương không hợp lệ.');
        $minutes=0; $ot=0;
        foreach ($sheets as $s) { if ($s['review_status']!=='approved') throw new Problem(409,'Chỉ tính lương từ bảng công đã duyệt.'); $minutes+=(int)$s['worked_minutes']-(int)$s['overtime_minutes']; $ot+=(int)$s['overtime_minutes']; }
        $minutes=max(0,$minutes)+$paidLeaveMinutes;
        $den=match($employee['salary_type']??'monthly') { 'hourly'=>60,'daily'=>$dayMinutes,default=>$days*$dayMinutes };
        $earned=self::productDivide([$base,min($minutes,($employee['salary_type']??'monthly')==='monthly'?$den:PHP_INT_MAX)],$den);
        $overtime=self::productDivide([$base,$ot,(int)$policy['overtime_multiplier_bps']],$den*10000);
        $bonus=0;$deductions=0;$variable=0;
        foreach ($adjustments as $a) {
            if ($a['status']!=='approved') throw new Problem(409,'Khoản điều chỉnh chưa được duyệt.');
            match($a['kind']) { 'bonus'=>$bonus+=(int)$a['amount'],'allowance'=>$variable+=(int)$a['amount'],'deduction'=>$deductions+=(int)$a['amount'],default=>throw new Problem(422,'Khoản điều chỉnh không hợp lệ.') };
        }
        $custom=[];$customEarning=0;$employer=0;
        foreach(json_decode($policy['components']??'[]',true)??[] as $component) {
            $amount=isset($component['basis_points'])?self::productDivide([$earned,(int)$component['basis_points']],10000):(int)($component['amount']??0);
            if($amount<0||!in_array($component['kind']??'', ['earning','deduction','employer']))throw new Problem(422,'Thành phần lương tùy chỉnh không hợp lệ.');
            match($component['kind']) {'earning'=>$customEarning+=$amount,'deduction'=>$deductions+=$amount,'employer'=>$employer+=$amount};
            $custom[]=['code'=>$component['code'],'kind'=>$component['kind'],'amount'=>$amount];
        }
        $gross=$earned+$allowance+$variable+$overtime+$bonus+$customEarning; $net=$gross-$deductions-$advances;
        return ['gross'=>$gross,'deductions'=>$deductions,'employer_cost'=>$employer,'statutory'=>0,'advances'=>$advances,'net'=>$net,'breakdown'=>['base_earned'=>$earned,'allowance'=>$allowance+$variable,'overtime'=>$overtime,'bonus'=>$bonus,'custom_components'=>$custom,'worked_minutes'=>$minutes,'overtime_minutes'=>$ot,'advances'=>$advances,'deductions'=>$deductions,'net'=>$net,'compliance'=>'non_statutory_estimate','warning'=>'Ước tính chưa gồm đầy đủ thuế và bảo hiểm bắt buộc.','policy_version'=>(int)$policy['version']]];
    }
    public function __construct(private Store $s) {}
    public function run(int $id): array
    {
        $this->s->ctx->require('payroll'); (new Subscriptions($this->s))->writable('payroll');
        return $this->s->transaction(function() use($id) {
            $this->s->lockTenant(); $p=$this->s->find('payroll-periods',$id);
            if (!in_array($p['status'],['draft','calculated'])) throw new Problem(409,'Chỉ tính lại bảng lương nháp hoặc đã tính.');
            $policy=$this->s->find('salary-policies',(int)$p['policy_id']);
            if ($policy['effective_from']>$p['start_date']) throw new Problem(422,'Chính sách chưa có hiệu lực tại đầu kỳ.');
            $q=$this->s->query('employees'); if ($p['branch_id']) $q->where('branch_id',$p['branch_id']); if ($p['department_id']) $q->where('department_id',$p['department_id']);
            $employees=$q->where('hire_date <=',$p['end_date'])->get()->getResultArray(); $total=0;
            foreach ($employees as $e) {
                $sheets=$this->s->query('timesheets')->where('employee_id',$e['id'])->where('work_date >=',$p['start_date'])->where('work_date <=',$p['end_date'])->get()->getResultArray();
                $schedules=$this->s->query('schedules')->where('employee_id',$e['id'])->where('work_date >=',$p['start_date'])->where('work_date <=',$p['end_date'])->where('status !=','cancelled')->countAllResults();
                if (count($sheets)<$schedules) throw new Problem(409,'Còn ca làm chưa tổng hợp công: '.$e['full_name']);
                $adjust=$this->s->query('payroll-adjustments')->where('employee_id',$e['id'])->where('payroll_period_id',$id)->get()->getResultArray();
                $adv=$this->s->query('salary-advances')->where('employee_id',$e['id'])->where('payroll_period_id',$id)->where('status','approved')->get()->getResultArray();
                $leave=$this->s->query('leave-requests')->where('employee_id',$e['id'])->where('status','approved')->where('start_date <=',$p['end_date'])->where('end_date >=',$p['start_date'])->get()->getResultArray();
                $paidMinutes=0;
                foreach ($leave as $l) {
                    $type=$this->s->find('leave-types',(int)$l['leave_type_id']); if (!$type['paid']) continue;
                    $from=max($l['start_date'],$p['start_date']);$to=min($l['end_date'],$p['end_date']);
                    for ($d=new \DateTimeImmutable($from);$d<=new \DateTimeImmutable($to);$d=$d->modify('+1 day')) {
                        if (!in_array((int)$d->format('N'),json_decode($this->s->ctx->tenant['working_days']??'[1,2,3,4,5,6]',true))) continue;
                        if (array_filter($sheets,fn($s)=>$s['work_date']===$d->format('Y-m-d')&&(int)$s['worked_minutes']>0)) throw new Problem(409,'Công và phép hưởng lương trùng ngày.');
                        $paidMinutes+=(int)$l['half_day']?intdiv((int)$policy['standard_minutes'],2):(int)$policy['standard_minutes'];
                    }
                }
                $out=self::calculate($e,$sheets,$policy,$adjust,array_sum(array_column($adv,'amount')),$paidMinutes);
                $out['inputs']=json_encode(['employee'=>$e,'timesheets'=>$sheets,'policy'=>$policy,'adjustments'=>$adjust,'advances'=>$adv,'paid_leave_minutes'=>$paidMinutes],JSON_THROW_ON_ERROR);
                $out['breakdown']=json_encode($out['breakdown'],JSON_THROW_ON_ERROR); $out+=['employee_id'=>$e['id'],'payroll_period_id'=>$id,'status'=>'draft'];
                $old=$this->s->query('payslips')->where('employee_id',$e['id'])->where('payroll_period_id',$id)->get()->getRowArray();
                $old?$this->s->update('payslips',(int)$old['id'],$out):$this->s->insert('payslips',$out); $total+=$out['net'];
            }
            return $this->s->update('payroll-periods',$id,['status'=>'calculated','total_net'=>$total,'calculation_version'=>(int)$p['calculation_version']+1]);
        });
    }
    public function transition(int $id,string $action): array
    {
        $this->s->ctx->require('payroll'); (new Subscriptions($this->s))->writable('payroll');
        return $this->s->transaction(function() use($id,$action) {
            $this->s->lockTenant();$p=$this->s->find('payroll-periods',$id);
            [$from,$to]=match($action) { 'review'=>['calculated','reviewed'],'approve'=>['reviewed','approved'],'lock'=>['approved','locked'],'publish'=>['locked','published'],default=>throw new Problem(422,'Thao tác không hợp lệ.') };
            if ($p['status']!==$from) throw new Problem(409,'Sai thứ tự xử lý bảng lương.');
            if ($action==='publish') {
                foreach ($this->s->query('payslips')->where('payroll_period_id',$id)->get()->getResultArray() as $slip) {
                    $this->s->update('payslips',(int)$slip['id'],['status'=>'published']);
                    $e=$this->s->find('employees',(int)$slip['employee_id']);
                    if ($e['user_id']) (new Notifications($this->s))->send((int)$e['user_id'],'payroll:'.$id.':'.$e['id'],'Phiếu lương đã phát hành','Mở ứng dụng để xem phiếu lương.');
                }
            }
            return $this->s->update('payroll-periods',$id,['status'=>$to]);
        });
    }
}
