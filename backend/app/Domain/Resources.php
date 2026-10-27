<?php
namespace App\Domain;
final class Resources
{
    public function __construct(private Store $s) {}
    public static function permission(string $r): string
    {
        return match($r) {
            'employees','departments','branches','files'=>'people', 'shifts','schedules','shift-swaps','holidays'=>'schedule',
            'attendance','timesheets','attendance-corrections'=>'attendance','leave-requests','leave-types'=>'leave',
            'payroll-periods','payslips','payroll-adjustments','salary-advances','salary-policies','legal-rules'=>'payroll',
            'notifications','privacy-requests'=>'self',default=>'settings'
        };
    }
    public function authorize(string $r): void
    {
        if (!isset(Schema::RESOURCES[$r])) throw new Problem(404,'Tài nguyên không tồn tại.');
        if(($this->s->ctx->membership['role']??'')==='manager'&&in_array($r,['employees','branches','departments'])){$this->s->ctx->require('schedule');return;}
        if ($this->s->ctx->isEmployee()&&in_array($r,['employees','shifts','schedules','attendance','timesheets','attendance-corrections','leave-types','leave-requests','salary-advances','payslips','notifications','privacy-requests','shift-swaps','files','holidays'])) return;
        if (in_array($r,['notifications','privacy-requests'])&&$this->s->ctx->membership) return;
        $this->s->ctx->require(self::permission($r));
    }
    public function list(string $r,array $filters): array
    {
        $this->authorize($r); $q=$this->s->query($r);
        foreach (['employee_id','branch_id','department_id','status','payroll_period_id','review_status'] as $k) if (isset($filters[$k])&&isset(Schema::RESOURCES[$r][$k])) $q->where($k,$filters[$k]);
        $date=isset(Schema::RESOURCES[$r]['work_date'])?'work_date':(isset(Schema::RESOURCES[$r]['start_date'])?'start_date':'created_at');
        if (!empty($filters['from'])) $q->where($date.' >=',$filters['from']);
        if (!empty($filters['to'])) $q->where($date.' <=',$filters['to'].($date==='created_at'?'T23:59:59Z':''));
        $search=$filters['search']??'';
        if ($search!=='') {
            $columns=array_intersect(['name','full_name','code','email','reason'],array_keys(Schema::RESOURCES[$r]));
            if ($columns) { $q->groupStart(); foreach ($columns as $c) $q->orLike($c,$search); $q->groupEnd(); }
        }
        $count=(clone $q)->countAllResults(); $limit=min(200,max(1,(int)($filters['limit']??25)));$page=max(1,(int)($filters['page']??1));
        $sort=in_array($filters['sort']??'',array_merge(['id','created_at'],array_keys(Schema::RESOURCES[$r])))?$filters['sort']:'id';
        $rows=$q->orderBy($sort,($filters['direction']??'desc')==='asc'?'ASC':'DESC')->limit($limit,($page-1)*$limit)->get()->getResultArray();
        return ['data'=>array_map(fn($row)=>$this->redact($r,$row),$rows),'meta'=>['total'=>$count,'page'=>$page,'limit'=>$limit]];
    }
    public function redact(string $r,array $row): array
    {
        unset($row['emergency_contact']);
        if ($r==='employees') {
            try { $this->s->ctx->require('payroll'); } catch (Problem) { unset($row['base_salary'],$row['allowance'],$row['salary_type']); }
        }
        if ($r==='files') unset($row['storage_key']);
        return $row;
    }
    private function entitlement(string $r): void
    {
        (new Subscriptions($this->s))->writable(match($r) {
            'payroll-periods','payroll-adjustments','salary-policies','salary-advances','legal-rules'=>'payroll',
            'leave-requests','leave-types'=>'leave','shift-swaps'=>'advanced_scheduling',default=>null
        });
    }
    public function save(string $r,array $input,?int $id=null): array
    {
        $this->authorize($r);$this->entitlement($r);
        if(in_array($r,['employees','branches','departments','files']))$this->s->ctx->require('people');
        $readonly=['attendance','timesheets','payslips','notifications','files'];
        if (in_array($r,$readonly)) throw new Problem(405,'Dùng thao tác nghiệp vụ dành riêng cho dữ liệu này.');
        if ($this->s->ctx->isEmployee()&&!in_array($r,['leave-requests','salary-advances','attendance-corrections','privacy-requests','shift-swaps'])) throw new Problem(403,'Bạn chỉ có thể gửi yêu cầu cá nhân.');
        return $this->s->transaction(function() use($r,$input,$id) {
            $this->s->lockTenant(); $old=$id?$this->s->find($r,$id):null;
            if ($old&&isset($old['status'])&&in_array($old['status'],['approved','locked','published'])) throw new Problem(409,'Không thể sửa dữ liệu đã duyệt hoặc khóa.');
            if ($old&&$this->s->ctx->isEmployee()) throw new Problem(403,'Gửi yêu cầu mới để thay đổi dữ liệu.');
            foreach (['status','review_status','approved_by','original_values','total_net','calculation_version','avatar_file_id'] as $key) unset($input[$key]);
            if ($this->s->ctx->isEmployee()) {
                if ($r==='privacy-requests') $input['user_id']=$this->s->ctx->user['id'];
                else $input['employee_id']=$this->s->ctx->membership['employee_id'];
            }
            elseif(!isset($input['employee_id'])&&!empty($this->s->ctx->membership['employee_id'])&&in_array($r,['leave-requests','salary-advances','attendance-corrections','shift-swaps']))$input['employee_id']=$this->s->ctx->membership['employee_id'];
            if ($r==='employees') {
                if (!$id) (new Subscriptions($this->s))->employeeLimit();
                if (!empty($input['user_id'])) throw new Problem(422,'Liên kết tài khoản thông qua lời mời.');
                if (array_intersect(['base_salary','allowance','salary_type'],array_keys($input))) $this->s->ctx->require('payroll');
                if (isset($input['employment_type'])&&!in_array($input['employment_type'],['full_time','part_time','hourly','daily','contract'])) throw new Problem(422,'Loại hợp đồng không hợp lệ.');
                if (isset($input['salary_type'])&&!in_array($input['salary_type'],['monthly','daily','hourly','part_time'])) throw new Problem(422,'Loại lương không hợp lệ.');
            }
            $v=(new Validation($this->s))->clean($r,$input,$id!==null);$merged=array_merge($old??[],$v);
            if ($r==='schedules') $this->schedule($merged,$id);
            if ($r==='shifts') {
                if (($merged['break_minutes']??0)>1440||($merged['grace_minutes']??0)>1440) throw new Problem(422,'Số phút vượt giới hạn.');
                $v+=['kind'=>'fixed','earliest_minutes'=>120,'latest_minutes'=>240,'working_days'=>'[1,2,3,4,5,6]'];
            }
            if ($r==='branches') {
                if (!$id&&$this->s->query('branches')->countAllResults()>0) (new Subscriptions($this->s))->writable('branches');
                $v+=['timezone'=>$this->s->ctx->tenant['timezone'],'attendance_policy'=>'basic','radius'=>100,'active'=>1];
                if (!in_array($v['attendance_policy'],['basic','gps','wifi'])) throw new Problem(422,'Chính sách chấm công không hợp lệ.');
                if ($v['attendance_policy']==='gps'&&(!isset($v['latitude'],$v['longitude'])||abs((float)$v['latitude'])>90||abs((float)$v['longitude'])>180)) throw new Problem(422,'Tọa độ chi nhánh không hợp lệ.');
            }
            if ($r==='leave-requests') $v['days']=$this->leaveDays($merged,$id);
            if ($r==='attendance-corrections') {
                $ts=$this->s->find('timesheets',(int)$v['timesheet_id']);
                if ((int)$ts['employee_id']!==(int)$v['employee_id']) throw new Problem(422,'Bảng công không thuộc nhân viên.');
                $new=json_decode($v['new_values'],true);
                if (!is_array($new)||array_diff(array_keys($new),['check_in','check_out','reason'])) throw new Problem(422,'Chỉ điều chỉnh giờ vào/ra.');
                $v['original_values']=json_encode($ts);
            }
            if ($r==='payroll-periods') {
                if ($merged['start_date']>$merged['end_date']) throw new Problem(422,'Ngày kết thúc không hợp lệ.');
                $q=$this->s->query($r)->where('start_date <=',$merged['end_date'])->where('end_date >=',$merged['start_date']);
                if ($id) $q->where('id !=',$id);
                if ($q->countAllResults()) throw new Problem(409,'Kỳ lương trùng kỳ hiện có.');
            }
            if ($r==='payroll-adjustments'||($r==='salary-advances'&&!empty($merged['payroll_period_id']))) $this->unlocked((int)$merged['payroll_period_id']);
            if ($r==='salary-policies') {
                if ($id&&$this->s->query('payroll-periods')->where('policy_id',$id)->countAllResults()) throw new Problem(409,'Chính sách đã sử dụng; hãy tạo phiên bản mới.');
                $v+=['standard_days'=>26,'standard_minutes'=>480,'overtime_multiplier_bps'=>10000,'version'=>1];
                $components=json_decode($v['components']??'[]',true);
                if(!is_array($components)||count($components)>50)throw new Problem(422,'Tối đa 50 thành phần lương.');
                foreach($components as $c)if(!is_array($c)||!preg_match('/^[a-zA-Z0-9_]{1,40}$/',$c['code']??'')||!in_array($c['kind']??'', ['earning','deduction','employer'])||(!isset($c['amount'])&&!isset($c['basis_points']))||isset($c['amount'])&&(!is_int($c['amount'])||$c['amount']<0||$c['amount']>999999999999)||isset($c['basis_points'])&&(!is_int($c['basis_points'])||$c['basis_points']<0||$c['basis_points']>100000))throw new Problem(422,'Thành phần lương cần code, kind và amount số nguyên hoặc basis_points.');
            }
            if($r==='leave-types'&&$id&&$this->s->query('leave-requests')->where('leave_type_id',$id)->countAllResults())throw new Problem(409,'Loại phép đã sử dụng; hãy tạo cấu hình hiệu lực mới.');
            if ($r==='legal-rules') $v['verified']=0; // Never present unreviewed legal configuration as verified.
            if (!$id) {
                if (isset(Schema::RESOURCES[$r]['status'])) $v['status']=match($r) { 'employees'=>'active','schedules'=>'scheduled','payroll-periods'=>'draft',default=>'pending' };
                if ($r==='employees') $v+=['salary_type'=>'monthly'];
                if ($r==='privacy-requests') $v['user_id']=$this->s->ctx->user['id'];
            }
            $out=$id?$this->s->update($r,$id,$v):$this->s->insert($r,$v);
            if (!$id&&in_array($r,['leave-requests','attendance-corrections','salary-advances','shift-swaps'])) (new Notifications($this->s))->managers($r.':'.$out['id'],'Yêu cầu mới','Có yêu cầu đang chờ duyệt.');
            return $this->redact($r,$out);
        });
    }
    public function unlocked(int $period): void
    {
        $p=$this->s->find('payroll-periods',$period);
        if (!in_array($p['status'],['draft','calculated'])) throw new Problem(409,'Kỳ lương đã chuyển sang xét duyệt hoặc khóa.');
    }
    public function schedule(array $v,?int $id=null): void
    {
        $shift=$this->s->find('shifts',(int)$v['shift_id']); $branch=$this->s->find('branches',(int)$v['branch_id']);
        if (($shift['effective_from']&&$v['work_date']<$shift['effective_from'])||($shift['effective_to']&&$v['work_date']>$shift['effective_to'])) throw new Problem(422,'Ca chưa có hiệu lực.');
        [$a,$b]=AttendanceEngine::window($v,$shift,$branch['timezone']?:$this->s->ctx->tenant['timezone']);
        $q=$this->s->query('schedules')->where('employee_id',$v['employee_id'])->where('status !=','cancelled')->where('work_date >=',(new \DateTimeImmutable($v['work_date']))->modify('-1 day')->format('Y-m-d'))->where('work_date <=',(new \DateTimeImmutable($v['work_date']))->modify('+1 day')->format('Y-m-d'));
        if ($id) $q->where('id !=',$id);
        foreach ($q->get()->getResultArray() as $other) {
            $os=$this->s->find('shifts',(int)$other['shift_id']);$ob=$this->s->find('branches',(int)$other['branch_id']);
            [$c,$d]=AttendanceEngine::window($other,$os,$ob['timezone']?:$this->s->ctx->tenant['timezone']);
            if ($a<$d&&$c<$b) throw new Problem(409,'Ca làm bị trùng giờ.');
        }
    }
    private function leaveDays(array $v,?int $id): string
    {
        if ($v['start_date']>$v['end_date']) throw new Problem(422,'Khoảng ngày nghỉ không hợp lệ.');
        if (($v['half_day']??0)&&$v['start_date']!==$v['end_date']) throw new Problem(422,'Nửa ngày chỉ áp dụng cho một ngày.');
        if ((new \DateTimeImmutable($v['start_date']))->diff(new \DateTimeImmutable($v['end_date']))->days>366) throw new Problem(422,'Yêu cầu nghỉ tối đa 366 ngày.');
        $q=$this->s->query('leave-requests')->where('employee_id',$v['employee_id'])->whereIn('status',['pending','approved'])->where('start_date <=',$v['end_date'])->where('end_date >=',$v['start_date']); if ($id) $q->where('id !=',$id);
        if ($q->countAllResults()) throw new Problem(409,'Yêu cầu nghỉ trùng ngày.');
        $type=$this->s->find('leave-types',(int)$v['leave_type_id']); if ($type['effective_from']>$v['start_date']) throw new Problem(422,'Loại phép chưa có hiệu lực.');
        $days=0; $working=json_decode($this->s->ctx->tenant['working_days']??'[1,2,3,4,5,6]',true);
        for ($d=new \DateTimeImmutable($v['start_date']);$d<=new \DateTimeImmutable($v['end_date']);$d=$d->modify('+1 day')) {
            if (!in_array((int)$d->format('N'),$working)) continue;
            if ($this->s->query('holidays')->where('work_date',$d->format('Y-m-d'))->countAllResults()) continue;
            $days+=2;
        }
        if ($v['half_day']??0) $days=intdiv($days,2);
        if (!$days) throw new Problem(422,'Khoảng nghỉ không có ngày làm việc.');
        return (string)($days/2);
    }
    public function archive(string $r,int $id): array
    {
        $this->authorize($r); $this->entitlement($r); if ($this->s->ctx->isEmployee()) throw new Problem(403,'Không có quyền lưu trữ.');
        if(in_array($r,['employees','branches','departments','files']))$this->s->ctx->require('people');
        if (!in_array($r,['employees','departments','branches','shifts','leave-types','holidays'])) throw new Problem(405,'Dữ liệu nghiệp vụ không thể xóa trực tiếp.');
        return $this->s->transaction(function() use($r,$id) { $this->s->lockTenant(); $this->s->find($r,$id);
            if ($r==='employees') {
                $e=$this->s->find($r,$id);
                if ($e['user_id']) { $this->s->db->table('tenant_memberships')->where('tenant_id',$this->s->ctx->tenantId())->where('user_id',$e['user_id'])->update(['deleted_at'=>Store::now()]); $this->s->db->table('auth_tokens')->where('user_id',$e['user_id'])->update(['revoked'=>1]); }
                $this->s->update($r,$id,['status'=>'inactive']);
            }
            $this->s->update($r,$id,['deleted_at'=>Store::now()]); return ['archived'=>true];
        });
    }
}
