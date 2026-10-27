<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
use App\Domain\{Context,Store,Onboarding,Resources,AttendanceEngine};
final class DemoSeeder extends Seeder
{
    public function run()
    {
        if (ENVIRONMENT==='production') throw new \RuntimeException('Demo seed is prohibited in production.');
        $this->call('PlatformSeeder');
        if ($this->db->table('users')->where('email','owner@minihrm.test')->countAllResults()) return;
        $password=env('DEMO_PASSWORD','MiniHrm!2026Demo');
        foreach ([['owner@minihrm.test','Nguyễn Minh Anh',0],['super@minihrm.test','Quản trị hệ thống',1],['other@minihrm.test','Chủ công ty B',0]] as [$email,$name,$super]) {
            $this->db->table('users')->insert(['email'=>$email,'full_name'=>$name,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'verified_at'=>Store::now(),'is_super'=>$super,'active'=>1]);
        }
        foreach (['owner@minihrm.test'=>'Công ty An Nhiên','other@minihrm.test'=>'Công ty Bình Minh'] as $email=>$name) {
            $u=$this->db->table('users')->where('email',$email)->get()->getRowArray();
            (new Onboarding($this->db))->create(new Context($u),['name'=>$name,'timezone'=>'Asia/Ho_Chi_Minh','business_type'=>'office','branch_name'=>'Văn phòng TP. Hồ Chí Minh']);
            $m=$this->db->table('tenant_memberships')->where('user_id',$u['id'])->get()->getRowArray();$t=$this->db->table('tenants')->where('id',$m['tenant_id'])->get()->getRowArray();$s=new Store($this->db,new Context($u,$m,$t));
            $dep=$s->insert('departments',['name'=>'Vận hành']);$branch=$s->query('branches')->get()->getRowArray();$shift=$s->query('shifts')->get()->getRowArray();
            foreach (['Trần Hoàng Nam','Lê Thu Hà','Phạm Quốc Bảo','Võ Ngọc Linh'] as $i=>$fullName) {
                $e=(new Resources($s))->save('employees',['code'=>'NV00'.($i+1),'full_name'=>$fullName,'email'=>'nv'.($i+1).'.'.$t['id'].'@minihrm.test','branch_id'=>$branch['id'],'department_id'=>$dep['id'],'hire_date'=>gmdate('Y-01-01'),'employment_type'=>'full_time','job_title'=>$i===0?'Trưởng nhóm':'Chuyên viên','salary_type'=>'monthly','base_salary'=>8000000+$i*500000,'allowance'=>500000]);
                if ($i===0&&$email==='owner@minihrm.test') {
                    $this->db->table('users')->insert(['email'=>'employee@minihrm.test','full_name'=>$fullName,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'verified_at'=>Store::now(),'active'=>1]);$uid=(int)$this->db->insertID();
                    $s->insert('tenant-memberships',['user_id'=>$uid,'role'=>'employee','employee_id'=>$e['id'],'permissions'=>'[]','branch_ids'=>'[]']);$s->update('employees',(int)$e['id'],['user_id'=>$uid]);
                }
                $today=(new \DateTimeImmutable('now',new \DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
                for ($j=6;$j>=0;$j--) {
                    $date=(new \DateTimeImmutable($today))->modify('-'.$j.' days')->format('Y-m-d');
                    $sc=(new Resources($s))->save('schedules',['employee_id'=>$e['id'],'shift_id'=>$shift['id'],'branch_id'=>$branch['id'],'work_date'=>$date]);
                    if ($j>0||$i<3) {
                        foreach (['check_in'=>'01:'.($i===2?'15':'00').':00Z','check_out'=>'10:00:00Z'] as $kind=>$time) $s->insert('attendance',['employee_id'=>$e['id'],'schedule_id'=>$sc['id'],'kind'=>$kind,'occurred_at'=>$date.'T'.$time,'status'=>'verified','idempotency_key'=>bin2hex(random_bytes(16))]);
                    }
                    (new AttendanceEngine($s))->aggregate($sc);
                }
            }
        }
    }
}
