<?php
namespace App\Domain;
final class Onboarding
{
    public function __construct(private \CodeIgniter\Database\BaseConnection $db) {}
    public function create(Context $ctx,array $v): array
    {
        if ($ctx->tenant||$ctx->user['is_super']) throw new Problem(409,'Tài khoản không thể tạo thêm công ty.');
        if (!trim($v['name']??'')||!in_array($v['timezone']??'',\DateTimeZone::listIdentifiers())) throw new Problem(422,'Nhập tên công ty và múi giờ hợp lệ.');
        if (!in_array($v['business_type']??'', ['retail','cafe','restaurant','office','service','construction','other'])) throw new Problem(422,'Loại hình kinh doanh không hợp lệ.');
        $presets=['office'=>['08:00','17:00',[1,2,3,4,5]],'retail'=>['09:00','18:00',[1,2,3,4,5,6,7]],'cafe'=>['06:00','14:00',[1,2,3,4,5,6,7]],'restaurant'=>['10:00','22:00',[1,2,3,4,5,6,7]],'construction'=>['07:00','17:00',[1,2,3,4,5,6]]];
        $preset=$presets[$v['business_type']]??['08:00','17:00',[1,2,3,4,5,6]];
        $working=$v['working_days']??$preset[2]; if (!is_array($working)||!$working||array_diff($working,[1,2,3,4,5,6,7])) throw new Problem(422,'Ngày làm việc không hợp lệ.');
        $this->db->transBegin();
        try {
            if ($this->db->DBDriver==='MySQLi') $this->db->query('SELECT id FROM users WHERE id = ? FOR UPDATE',[$ctx->user['id']]);
            if ($this->db->table('tenant_memberships')->where('user_id',$ctx->user['id'])->countAllResults()) throw new Problem(409,'Tài khoản đã có công ty.');
            $this->db->table('tenants')->insert(['name'=>mb_substr($v['name'],0,255),'business_type'=>$v['business_type'],'timezone'=>$v['timezone'],'working_days'=>json_encode($working),'payroll_day'=>max(1,min(28,(int)($v['payroll_day']??1))),'onboarded'=>1,'status'=>'active','retention_days'=>365,'created_at'=>Store::now()]);$tid=(int)$this->db->insertID();
            $tenant=$this->db->table('tenants')->where('id',$tid)->get()->getRowArray();
            $m=['tenant_id'=>$tid,'user_id'=>$ctx->user['id'],'role'=>'owner','permissions'=>'[]','branch_ids'=>'[]'];$this->db->table('tenant_memberships')->insert($m);
            $s=new Store($this->db,new Context($ctx->user,$m,$tenant));$plan=$this->db->table('plans')->where('name','Business')->get()->getRowArray();
            if (!$plan) throw new Problem(503,'Cần chạy seeder gói dịch vụ.');
            $s->insert('subscriptions',['plan_id'=>$plan['id'],'status'=>'trial','starts_at'=>gmdate('Y-m-d'),'ends_at'=>gmdate('Y-m-d',time()+14*86400)]);
            $branch=(new Resources($s))->save('branches',['name'=>$v['branch_name']??'Trụ sở chính','address'=>$v['address']??'','timezone'=>$v['timezone']]);
            $shift=(new Resources($s))->save('shifts',['name'=>'Ca mặc định','start_time'=>$v['start_time']??$preset[0],'end_time'=>$v['end_time']??$preset[1],'break_minutes'=>60,'grace_minutes'=>5,'overtime_eligible'=>1,'working_days'=>$working]);
            $s->insert('salary-policies',['name'=>'Chính sách khởi tạo','effective_from'=>gmdate('Y-01-01'),'standard_days'=>26,'standard_minutes'=>480,'overtime_multiplier_bps'=>10000,'version'=>1,'components'=>'[]']);
            foreach ([['Nghỉ phép năm',1,0],['Nghỉ không lương',0,0],['Nghỉ ốm',0,0],['Việc riêng',0,0]] as [$name,$paid,$days]) $s->insert('leave-types',['name'=>$name,'paid'=>$paid,'annual_days'=>$days,'carryover_days'=>0,'effective_from'=>gmdate('Y-01-01')]);
            $this->db->transCommit();return ['tenant'=>$tenant,'branch'=>$branch,'shift'=>$shift];
        } catch (\Throwable $e) { $this->db->transRollback();throw $e; }
    }
}
