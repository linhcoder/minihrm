<?php
namespace App\Domain;
final class Workflow
{
    public function __construct(private Store $s) {}
    public function act(string $r,int $id,string $action,array $v=[]): array
    {
        if ($r==='payroll-periods') return $action==='calculate'?(new PayrollEngine($this->s))->run($id):(new PayrollEngine($this->s))->transition($id,$action);
        if ($r==='notifications'&&$action==='read') return $this->s->update($r,$id,['read_at'=>Store::now()]);
        if ($r==='employees'&&$action==='invite') return $this->invite($id);
        if($r==='employees'&&$action==='avatar'){
            $this->s->ctx->require('people');$this->s->find('employees',$id);$raw=base64_decode($v['content']??'',true);
            if(!$raw||!in_array((new \finfo(FILEINFO_MIME_TYPE))->buffer($raw),['image/jpeg','image/png']))throw new Problem(422,'Ảnh đại diện phải là JPEG hoặc PNG.');
            return $this->s->transaction(function() use($id,$v){$file=(new Files($this->s))->upload([...$v,'employee_id'=>$id]);return $this->s->update('employees',$id,['avatar_file_id'=>$file['id']]);});
        }
        if ($r==='employees'&&in_array($action,['activate','deactivate'])) {
            $this->s->ctx->require('people'); (new Subscriptions($this->s))->writable();
            return $this->s->transaction(function() use($id,$action) { $this->s->lockTenant();$e=$this->s->find('employees',$id);
                if ($action==='activate'&&$e['status']!=='active') (new Subscriptions($this->s))->employeeLimit();
                if ($e['user_id']&&$action==='deactivate') $this->s->db->table('auth_tokens')->where('user_id',$e['user_id'])->update(['revoked'=>1]);
                return $this->s->update('employees',$id,['status'=>$action==='activate'?'active':'inactive']); });
        }
        if (!in_array($r,['attendance','timesheets','attendance-corrections','leave-requests','salary-advances','payroll-adjustments','shift-swaps','privacy-requests'])) throw new Problem(404,'Không tìm thấy thao tác.');
        if (!in_array($action,['approve','reject'])) throw new Problem(422,'Thao tác không hợp lệ.');
        $this->s->ctx->require($r==='privacy-requests'?'settings':Resources::permission($r)); (new Subscriptions($this->s))->writable();
        return $this->s->transaction(function() use($r,$id,$action,$v) {
            $this->s->lockTenant();$row=$this->s->find($r,$id);$approve=$action==='approve';
            $field=$r==='timesheets'?'review_status':'status';
            if (!in_array($row[$field],['pending','pending_verification','pending_correction'])) throw new Problem(409,'Yêu cầu đã xử lý.');
            if ($r==='timesheets') {
                if ($approve&&(in_array($row['status'],['missing_check_out','pending_verification','pending_correction'])||(!empty($row['check_in'])&&empty($row['check_out'])))) throw new Problem(409,'Cần xử lý thiếu giờ ra hoặc xác minh trước.');
                $this->assertNoLockedPayroll((int)$row['employee_id'],$row['work_date']);
            }
            if ($r==='attendance'&&$approve) {
                if (empty($v['verified_at'])||empty($v['reason'])) throw new Problem(422,'Cần giờ UTC đã xác minh và lý do.');
                try { $dt=new \DateTimeImmutable($v['verified_at']); } catch (\Throwable) { throw new Problem(422,'Giờ xác minh không hợp lệ.'); }
                $schedule=$this->s->find('schedules',(int)$row['schedule_id']);$this->assertNoLockedPayroll((int)$row['employee_id'],$schedule['work_date']);
                $this->s->update($r,$id,['occurred_at'=>$dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),'status'=>'verified']);
                (new AttendanceEngine($this->s))->aggregate($schedule); return $this->s->find($r,$id);
            }
            if($r==='attendance'&&!$approve){$this->s->update($r,$id,['status'=>'rejected']);(new AttendanceEngine($this->s))->aggregate($this->s->find('schedules',(int)$row['schedule_id']));return $this->s->find($r,$id);}
            if ($r==='attendance-corrections'&&$approve) {
                $t=$this->s->find('timesheets',(int)$row['timesheet_id']); $this->assertNoLockedPayroll((int)$row['employee_id'],$t['work_date']);
                $new=json_decode($row['new_values'],true);$a=$new['check_in']??$t['check_in'];$b=$new['check_out']??$t['check_out'];
                try { $in=new \DateTimeImmutable($a);$out=new \DateTimeImmutable($b); } catch (\Throwable) { throw new Problem(422,'Giờ điều chỉnh không hợp lệ.'); }
                if (!$a||!$b||$out<=$in||$out->getTimestamp()-$in->getTimestamp()>172800) throw new Problem(422,'Giờ vào/ra không hợp lệ.');
                $schedule=$this->s->find('schedules',(int)$t['schedule_id']);$shift=$this->s->find('shifts',(int)$schedule['shift_id']);$branch=$this->s->find('branches',(int)$schedule['branch_id']);
                $normalized=AttendanceEngine::normalize([['kind'=>'check_in','occurred_at'=>$a,'status'=>'verified'],['kind'=>'check_out','occurred_at'=>$b,'status'=>'verified']],$schedule,$shift,$branch['timezone']?:$this->s->ctx->tenant['timezone']);
                $this->s->update('timesheets',(int)$t['id'],$normalized);
            }
            if ($r==='leave-requests'&&$approve) {
                $this->assertNoLockedPayroll((int)$row['employee_id'],$row['start_date'],$row['end_date']);
                $type=$this->s->find('leave-types',(int)$row['leave_type_id']);
                $year=substr($row['start_date'],0,4);
                if (substr($row['end_date'],0,4)!==$year) throw new Problem(422,'Tách yêu cầu nghỉ theo năm.');
                $used=$this->s->query('leave-requests')->selectSum('days')->where('employee_id',$row['employee_id'])->where('leave_type_id',$row['leave_type_id'])->where('status','approved')->where('start_date >=',$year.'-01-01')->where('start_date <=',$year.'-12-31')->get()->getRowArray()['days']??0;
                if ($type['paid']&&(float)$used+(float)$row['days']>(int)$type['annual_days']+(int)$type['carryover_days']) throw new Problem(409,'Không đủ số ngày phép theo chính sách.');
                $sheets=$this->s->query('timesheets')->where('employee_id',$row['employee_id'])->where('work_date >=',$row['start_date'])->where('work_date <=',$row['end_date'])->get()->getResultArray();
                foreach ($sheets as $t) {
                    if ($t['review_status']==='approved'||(int)$t['worked_minutes']>0) throw new Problem(409,'Ngày nghỉ đã có công hoặc đã duyệt.');
                    $this->s->update('timesheets',(int)$t['id'],['status'=>'on_leave','worked_minutes'=>0,'review_status'=>'pending']);
                }
            }
            if (in_array($r,['salary-advances','payroll-adjustments'])&&$row['payroll_period_id']) (new Resources($this->s))->unlocked((int)$row['payroll_period_id']);
            if ($r==='shift-swaps'&&$approve) {
                $a=$this->s->find('schedules',(int)$row['schedule_id']);$b=$this->s->find('schedules',(int)$row['target_schedule_id']);
                if ((int)$a['employee_id']!==(int)$row['employee_id']||$a['work_date']<gmdate('Y-m-d')||$b['work_date']<gmdate('Y-m-d')) throw new Problem(422,'Ca đổi không hợp lệ.');
                $this->s->update('schedules',(int)$a['id'],['status'=>'cancelled']);$this->s->update('schedules',(int)$b['id'],['status'=>'cancelled']);
                $na=[...$a,'employee_id'=>$b['employee_id']];$nb=[...$b,'employee_id'=>$a['employee_id']];
                (new Resources($this->s))->schedule($na,(int)$a['id']);(new Resources($this->s))->schedule($nb,(int)$b['id']);
                $this->s->update('schedules',(int)$a['id'],['employee_id'=>$na['employee_id'],'status'=>'scheduled']);$this->s->update('schedules',(int)$b['id'],['employee_id'=>$nb['employee_id'],'status'=>'scheduled']);
            }
            $values=[$field=>$approve?'approved':'rejected']; if (isset(Schema::RESOURCES[$r]['approved_by'])) $values['approved_by']=$this->s->ctx->user['id'];
            if($r==='privacy-requests'&&$approve)(new Privacy($this->s))->fulfill($row);
            $result=$this->s->update($r,$id,$values);
            if (!empty($row['employee_id'])) {
                $e=$this->s->find('employees',(int)$row['employee_id']);if ($e['user_id']) (new Notifications($this->s))->send((int)$e['user_id'],$r.':'.$id.':'.$action,'Yêu cầu đã được xử lý',$approve?'Yêu cầu đã được duyệt.':'Yêu cầu đã bị từ chối.');
            }
            return $result;
        });
    }
    public function assertNoLockedPayroll(int $employee,string $from,?string $to=null): void
    {
        $to??=$from;
        $periods=$this->s->query('payroll-periods')->where('start_date <=',$to)->where('end_date >=',$from)->whereNotIn('status',['draft'])->get()->getResultArray();
        foreach ($periods as $p) if ($this->s->query('payslips')->where('employee_id',$employee)->where('payroll_period_id',$p['id'])->countAllResults()) throw new Problem(409,'Dữ liệu đã dùng tính lương. Điều chỉnh trong kỳ bổ sung sau khi được duyệt.');
    }
    private function invite(int $id): array
    {
        $this->s->ctx->require('invite'); (new Subscriptions($this->s))->writable();
        return $this->s->transaction(function() use($id) {
            $this->s->lockTenant();$e=$this->s->find('employees',$id);
            if ($e['user_id']) throw new Problem(409,'Nhân viên đã có tài khoản.');
            if ($this->s->db->table('users')->where('email',strtolower($e['email']))->countAllResults()) throw new Problem(409,'Email đã được sử dụng bởi tài khoản khác.');
            $this->s->db->table('users')->insert(['email'=>strtolower($e['email']),'full_name'=>$e['full_name'],'password_hash'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'active'=>1,'created_at'=>Store::now()]);$uid=(int)$this->s->db->insertID();
            $this->s->insert('tenant-memberships',['user_id'=>$uid,'role'=>'employee','employee_id'=>$id,'permissions'=>'[]','branch_ids'=>'[]']);
            $this->s->update('employees',$id,['user_id'=>$uid]);(new Auth($this->s->db))->challenge($uid,'reset');
            return ['message'=>'Đã tạo lời mời đặt mật khẩu.'];
        });
    }
}
