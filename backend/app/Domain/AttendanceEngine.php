<?php
namespace App\Domain;
final class AttendanceEngine
{
    public static function window(array $schedule,array $shift,string $timezone): array
    {
        $tz=new \DateTimeZone($timezone); $date=$schedule['work_date'];
        $start=new \DateTimeImmutable($date.' '.$shift['start_time'],$tz);
        $end=new \DateTimeImmutable($date.' '.$shift['end_time'],$tz);
        if ($end<=$start) $end=$end->modify('+1 day');
        return [$start->setTimezone(new \DateTimeZone('UTC')),$end->setTimezone(new \DateTimeZone('UTC'))];
    }
    public static function normalize(array $events,array $schedule,array $shift,string $tz,?\DateTimeImmutable $now=null): array
    {
        [$start,$end]=self::window($schedule,$shift,$tz); $worked=0; $open=null; $first=null; $last=null; $pending=false;
        usort($events,fn($a,$b)=>strcmp($a['occurred_at'],$b['occurred_at']));
        foreach ($events as $e) {
            if($e['status']==='rejected')continue;
            if ($e['status']!=='verified') { $pending=true; continue; }
            $t=new \DateTimeImmutable($e['occurred_at']);
            if ($e['kind']==='check_in'&&$open===null) { $open=$t; $first??=$t; }
            elseif ($e['kind']==='check_out'&&$open!==null) { $worked+=max(0,intdiv($t->getTimestamp()-$open->getTimestamp(),60)); $last=$t; $open=null; }
        }
        if ($open===null&&$first) $worked=max(0,$worked-((int)$shift['paid_break']?0:(int)$shift['break_minutes']));
        $late=$first?max(0,intdiv($first->getTimestamp()-$start->getTimestamp(),60)-(int)$shift['grace_minutes']):0;
        $early=$last?max(0,intdiv($end->getTimestamp()-$last->getTimestamp(),60)):0;
        if(($shift['kind']??'fixed')==='flexible') {$late=0;$early=0;}
        $expected=max(0,intdiv($end->getTimestamp()-$start->getTimestamp(),60)-((int)$shift['paid_break']?0:(int)$shift['break_minutes']));
        $now??=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));
        $state=$pending?'pending_verification':(!$first?'absent':($open?($now>$end?'missing_check_out':($late?'late':'present')):($late?'late':($early?'early_leave':'present'))));
        return ['check_in'=>$first?->format('Y-m-d\TH:i:s\Z'),'check_out'=>$open?null:$last?->format('Y-m-d\TH:i:s\Z'),'worked_minutes'=>$worked,'late_minutes'=>$late,'early_minutes'=>$early,'overtime_minutes'=>$shift['overtime_eligible']?max(0,$worked-$expected):0,'status'=>$state,'review_status'=>'pending'];
    }
    public static function distance(float $a,float $b,float $c,float $d): float
    {
        $x=sin(deg2rad($c-$a)/2)**2+cos(deg2rad($a))*cos(deg2rad($c))*sin(deg2rad($d-$b)/2)**2;
        return 6371000*2*atan2(sqrt($x),sqrt(max(0,1-$x)));
    }
    public function __construct(private Store $s) {}
    public function attempt(array $v): array
    {
        return $this->s->transaction(function() use ($v) {
            $this->s->lockTenant(); (new Subscriptions($this->s))->writable();
            $employee=$this->s->ctx->membership['employee_id']??0;
            if (!$employee) { $this->s->ctx->require('attendance'); $employee=(int)($v['employee_id']??0); }
            $emp=$this->s->find('employees',(int)$employee);
            if ($emp['status']!=='active') throw new Problem(403,'Nhân viên không hoạt động.');
            $key=$v['idempotency_key']??'';
            if (!preg_match('/^[a-zA-Z0-9-]{16,80}$/',$key)) throw new Problem(422,'Cần khóa idempotency hợp lệ.');
            $old=$this->s->query('attendance')->where('idempotency_key',$key)->get()->getRowArray();
            if ($old) { if ((int)$old['employee_id']!==(int)$employee||$old['kind']!==($v['kind']??'')) throw new Problem(409,'Khóa đã dùng cho thao tác khác.'); return $old; }
            if (!in_array($v['kind']??'', ['check_in','check_out'],true)) throw new Problem(422,'Loại chấm công không hợp lệ.');
            $schedule=$this->s->find('schedules',(int)($v['schedule_id']??0));
            if ((int)$schedule['employee_id']!==(int)$employee||$schedule['status']==='cancelled') throw new Problem(403,'Ca làm không thuộc nhân viên.');
            $shift=$this->s->find('shifts',(int)$schedule['shift_id']); $branch=$this->branch((int)$schedule['branch_id']);
            [$start,$end]=self::window($schedule,$shift,$branch['timezone']?:$this->s->ctx->tenant['timezone']);
            $now=new \DateTimeImmutable('now',new \DateTimeZone('UTC')); $offline=!empty($v['offline']);
            if (!$offline&&($now<$start->modify('-'.max(0,(int)$shift['earliest_minutes']).' minutes')||$now>$end->modify('+'.max(0,(int)$shift['latest_minutes']).' minutes'))) throw new Problem(422,'Ngoài khung giờ chấm công của ca.');
            $status=$offline?'pending_verification':'verified'; $policy=$branch['attendance_policy']??'basic';
            if ($policy==='gps') {
                (new Subscriptions($this->s))->writable('gps');
                if (!isset($v['latitude'],$v['longitude'],$v['accuracy'])||!is_numeric($v['latitude'])||!is_numeric($v['longitude'])||abs((float)$v['latitude'])>90||abs((float)$v['longitude'])>180) throw new Problem(422,'Cần vị trí hợp lệ theo chính sách GPS.');
                if ((float)$v['accuracy']<=0||(float)$v['accuracy']>max(1,(int)$branch['radius'])||!empty($v['mock_location'])) $status='pending_verification';
                if (self::distance((float)$v['latitude'],(float)$v['longitude'],(float)$branch['latitude'],(float)$branch['longitude'])>(int)$branch['radius']) throw new Problem(422,'Vị trí ngoài bán kính cho phép.');
            }
            if ($policy==='wifi') { // Android reports are evidence, never proof of presence.
                if (empty($v['wifi_bssid'])||strtolower($v['wifi_bssid'])!==strtolower($branch['wifi_bssid']??'')) throw new Problem(422,'Wi-Fi không khớp; gửi yêu cầu điều chỉnh nếu không đọc được Wi-Fi.');
                $status='pending_verification';
            }
            $last=$this->s->query('attendance')->where('schedule_id',$schedule['id'])->orderBy('id','DESC')->get()->getRowArray();
            if (!$offline&&(($last&&$last['kind']===$v['kind'])||(!$last&&$v['kind']==='check_out'))) throw new Problem(409,'Thứ tự chấm công không hợp lệ hoặc trùng lặp.');
            $row=['employee_id'=>$employee,'schedule_id'=>$schedule['id'],'kind'=>$v['kind'],'occurred_at'=>Store::now(),'offline'=>(int)$offline,'local_timestamp'=>$v['local_timestamp']??null,'status'=>$status,'idempotency_key'=>$key,'device_id'=>substr($v['device_id']??'',0,100)];
            if ($policy==='gps') foreach (['latitude','longitude','accuracy','mock_location'] as $k) $row[$k]=$v[$k]??0;
            $out=$this->s->insert('attendance',$row); $this->aggregate($schedule); return $out;
        });
    }
    private function branch(int $id): array
    {
        // Employee may read only their assigned branch through this internal lookup.
        $b=$this->s->db->table('branches')->where('tenant_id',$this->s->ctx->tenantId())->where('id',$id)->where('deleted_at',null)->get()->getRowArray();
        if (!$b) throw new Problem(404,'Không tìm thấy chi nhánh.'); return $b;
    }
    public function aggregate(array $schedule): array
    {
        $existing=$this->s->query('timesheets')->where('schedule_id',$schedule['id'])->get()->getRowArray();
        if ($existing&&$existing['review_status']==='approved') throw new Problem(409,'Bảng công đã duyệt. Cần yêu cầu điều chỉnh.');
        $shift=$this->s->find('shifts',(int)$schedule['shift_id']); $branch=$this->branch((int)$schedule['branch_id']);
        $events=$this->s->query('attendance')->where('schedule_id',$schedule['id'])->get()->getResultArray();
        $v=self::normalize($events,$schedule,$shift,$branch['timezone']?:$this->s->ctx->tenant['timezone']);
        $v+=['employee_id'=>$schedule['employee_id'],'schedule_id'=>$schedule['id'],'work_date'=>$schedule['work_date']];
        return $existing?$this->s->update('timesheets',(int)$existing['id'],$v):$this->s->insert('timesheets',$v);
    }
}
