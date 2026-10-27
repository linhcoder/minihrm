<?php
namespace App\Commands;
use CodeIgniter\CLI\{BaseCommand,CLI};
use App\Domain\{Context,Store,Notifications};
final class Maintenance extends BaseCommand
{
    protected $group='MiniHRM';protected $name='hrm:daily';protected $description='Subscription expiry notices, missing check-out alerts, location retention.';
    public function run(array $params)
    {
        $db=db_connect();foreach($db->table('tenants')->where('status','active')->get()->getResultArray() as $t) {
            $m=$db->table('tenant_memberships')->where('tenant_id',$t['id'])->where('role','owner')->where('deleted_at',null)->get()->getRowArray();if(!$m)continue;
            $u=$db->table('users')->where('id',$m['user_id'])->get()->getRowArray();$s=new Store($db,new Context($u,$m,$t));$sub=$s->query('subscriptions')->get()->getRowArray();
            if($sub&&$sub['ends_at']&&$sub['ends_at']<=gmdate('Y-m-d',time()+7*86400)) (new Notifications($s))->send((int)$u['id'],'expiry:'.$sub['id'].':'.$sub['ends_at'],'Gói dịch vụ sắp hết hạn','Mở mục Gói dịch vụ để xem và gia hạn.');
            if($sub&&$sub['ends_at']&&$sub['ends_at']<gmdate('Y-m-d')&&(!$sub['grace_until']||$sub['grace_until']<gmdate('Y-m-d'))) $s->update('subscriptions',(int)$sub['id'],['status'=>$sub['cancel_at_end']?'cancelled':'expired']);
            foreach($s->query('timesheets')->where('status','missing_check_out')->get()->getResultArray() as $sheet) (new Notifications($s))->managers('missing:'.$sheet['id'],'Thiếu giờ kết thúc ca','Có bảng công cần kiểm tra.');
            $cutoff=gmdate('Y-m-d\TH:i:s\Z',time()-max(30,(int)$t['retention_days'])*86400);
            $s->query('attendance')->where('created_at <',$cutoff)->update(['latitude'=>null,'longitude'=>null,'device_id'=>null]);
        }
        foreach(glob(WRITEPATH.'privacy/*.json')?:[] as $export)if(filemtime($export)<time()-7*86400)unlink($export);
        $db->table('login_attempts')->where('attempted_at <',time()-30*86400)->delete();
        CLI::write('Daily maintenance complete. Payroll history preserved.');
    }
}
