<?php
namespace App\Domain;
final class Dashboard
{
    public function __construct(private Store $s) {}
    public function company(): array
    {
        $cacheKey='hrm:dashboard:'.hash('sha256',$this->s->db->database.':'.json_encode($this->s->ctx));$redis=new RedisGateway();
        try{$cached=$redis->command(['GET',$cacheKey]);if($cached)return json_decode($cached,true,512,JSON_THROW_ON_ERROR);}catch(\Throwable){}
        $today=(new \DateTimeImmutable('now',new \DateTimeZone($this->s->ctx->tenant['timezone'])))->format('Y-m-d');
        $sheets=$this->s->query('timesheets')->where('work_date',$today)->get()->getResultArray();
        $scheduled=$this->s->query('schedules')->where('work_date',$today)->where('status !=','cancelled')->get()->getResultArray();
        $checked=array_unique(array_column(array_filter($sheets,fn($s)=>!empty($s['check_in'])),'employee_id'));
        $leaves=$this->s->query('leave-requests')->where('status','approved')->where('start_date <=',$today)->where('end_date >=',$today)->get()->getResultArray();
        $result=['date'=>$today,'employees'=>$this->s->query('employees')->where('status','active')->countAllResults(),'scheduled'=>count(array_unique(array_column($scheduled,'employee_id'))),'checked_in'=>count($checked),'late'=>count(array_filter($sheets,fn($s)=>(int)$s['late_minutes']>0)),'on_leave'=>count(array_unique(array_column($leaves,'employee_id'))),'pending_corrections'=>$this->s->query('attendance-corrections')->where('status','pending')->countAllResults(),'pending_leave'=>$this->s->query('leave-requests')->where('status','pending')->countAllResults()];
        $result['absent']=max(0,$result['scheduled']-$result['checked_in']-$result['on_leave']);
        $trend=[];for($i=6;$i>=0;$i--) { $date=(new \DateTimeImmutable($today))->modify('-'.$i.' days')->format('Y-m-d');$trend[]=['date'=>$date,'present'=>$this->s->query('timesheets')->where('work_date',$date)->where('check_in !=',null)->countAllResults(),'late'=>$this->s->query('timesheets')->where('work_date',$date)->where('late_minutes >',0)->countAllResults()]; }
        $result['trend']=$trend;$result['distribution']=$this->s->query('employees')->select('department_id, COUNT(*) AS total')->where('status','active')->groupBy('department_id')->get()->getResultArray();
        try { $this->s->ctx->require('payroll');$result['estimated_payroll']=(int)($this->s->query('payroll-periods')->selectSum('total_net')->where('status !=','published')->get()->getRowArray()['total_net']??0); } catch(Problem) {}
        try{$redis->command(['SET',$cacheKey,json_encode($result),'EX',15]);}catch(\Throwable){}
        return $result;
    }
}
