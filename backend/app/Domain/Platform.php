<?php
namespace App\Domain;
use CodeIgniter\Database\BaseConnection;
final class Platform
{
    public function __construct(private BaseConnection $db,private Context $ctx) {}
    public function handle(string $path,string $method,array $v,array $filters=[]): array
    {
        $this->db->table('platform_audit')->insert(['actor_id'=>$this->ctx->user['id'],'action'=>$method.' '.$path,'payload'=>json_encode(array_diff_key($v,['password'=>1])),'created_at'=>Store::now()]);
        $parts=explode('/',$path);$r=$parts[0];$id=(int)($parts[1]??0);$action=$parts[2]??'';
        if ($r==='dashboard') {
            $subs=$this->db->table('subscriptions')->get()->getResultArray();$mrr=0;$paying=0;$trial=0;$active=0;$cancel=0;
            foreach($subs as $s) { if ($s['status']==='trial') $trial++;if ($s['cancel_at_end']) $cancel++;if (in_array($s['status'],['active','trial'])&&(!$s['ends_at']||$s['ends_at']>=gmdate('Y-m-d'))) { $active++;$p=$this->db->table('plans')->where('id',$s['plan_id'])->get()->getRowArray();if ($s['status']==='active'&&(int)$p['price']>0) { $paying++;$mrr+=(int)$p['price']; } } }
            return ['companies'=>$this->db->table('tenants')->countAllResults(),'active'=>$active,'trial'=>$trial,'paying'=>$paying,'mrr'=>$mrr,'cancellations'=>$cancel,'active_users'=>$this->db->table('users')->where('active',1)->countAllResults(),'attendance_events'=>$this->db->table('attendance')->countAllResults(),'pending_jobs'=>$this->db->table('jobs')->where('completed_at',0)->countAllResults()];
        }
        if ($r==='health') return ['database'=>$this->db->query('SELECT 1')->getRowArray(),'php'=>PHP_VERSION,'queue_pending'=>$this->db->table('jobs')->where('completed_at',0)->countAllResults(),'failed_jobs'=>$this->db->table('jobs')->where('attempts >=',5)->where('completed_at',0)->countAllResults(),'email_configured'=>(bool)env('email.SMTPHost'),'push_configured'=>(bool)env('HRM_FCM_PROJECT'),'payment_configured'=>(bool)env('HRM_PAYMENT_WEBHOOK_SECRET')];
        if ($r==='audit') return ['data'=>$this->db->table('platform_audit')->orderBy('id','DESC')->limit(200)->get()->getResultArray()];
        if ($r==='tenants'&&$id) {
            $t=$this->db->table('tenants')->where('id',$id)->get()->getRowArray();if (!$t) throw new Problem(404,'Không tìm thấy công ty.');
            if ($method==='get') return ['tenant'=>$t,'subscription'=>$this->db->table('subscriptions')->where('tenant_id',$id)->get()->getRowArray(),'employees'=>$this->db->table('employees')->where('tenant_id',$id)->where('deleted_at',null)->countAllResults()];
            if (in_array($action,['suspend','reactivate'])) { $this->db->table('tenants')->where('id',$id)->update(['status'=>$action==='suspend'?'suspended':'active']);return ['saved'=>true]; }
            if ($action==='subscription') {
                $plan=$this->db->table('plans')->where('id',(int)($v['plan_id']??0))->get()->getRowArray();if (!$plan) throw new Problem(422,'Gói không hợp lệ.');
                if (!in_array($v['status']??'', ['active','trial','expired','cancelled'])) throw new Problem(422,'Trạng thái không hợp lệ.');
                $values=['plan_id'=>$plan['id'],'status'=>$v['status'],'employee_limit'=>max(0,(int)($v['employee_limit']??0)),'ends_at'=>$v['ends_at']??null,'grace_until'=>$v['grace_until']??null];
                foreach(['ends_at','grace_until'] as $date) if ($values[$date]&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$values[$date])) throw new Problem(422,'Ngày không hợp lệ.');
                $this->db->table('subscriptions')->where('tenant_id',$id)->update($values);return ['saved'=>true];
            }
        }
        if ($r==='tenants'&&$method==='post'&&!$id) {
            if (!filter_var($v['email']??'',FILTER_VALIDATE_EMAIL)) throw new Problem(422,'Cần email chủ công ty.');
            $this->db->transBegin();try {
                $this->db->table('users')->insert(['email'=>strtolower($v['email']),'full_name'=>$v['full_name']??$v['name'],'password_hash'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT),'active'=>1,'verified_at'=>Store::now()]);$uid=(int)$this->db->insertID();$u=$this->db->table('users')->where('id',$uid)->get()->getRowArray();
                $out=(new Onboarding($this->db))->create(new Context($u),$v);(new Auth($this->db))->challenge($uid,'reset');$this->db->transCommit();return $out;
            } catch(\Throwable $e) { $this->db->transRollback();throw $e; }
        }
        if ($r==='payments'&&$method==='post') return (new Billing($this->db))->settle((int)$v['invoice_id'],(int)$v['amount'],'manual',(string)$v['reference'],(int)$this->ctx->user['id']);
        if ($r==='plans'&&$method!=='get') {
            $features=$v['features']??[];if (is_string($features)) $features=json_decode($features,true);
            if (!trim($v['name']??'')||!is_array($features)||!is_numeric($v['price']??null)||(int)$v['price']<0||(int)($v['employee_limit']??0)<1) throw new Problem(422,'Thông tin gói không hợp lệ.');
            $row=['name'=>$v['name'],'price'=>(int)$v['price'],'employee_limit'=>(int)$v['employee_limit'],'features'=>json_encode($features)];
            if ($id) $this->db->table('plans')->where('id',$id)->update($row);else $this->db->table('plans')->insert($row);return ['saved'=>true];
        }
        if ($r==='settings') {
            if ($method==='get') return ['data'=>$this->db->table('settings')->get()->getResultArray()];
            if (!in_array($v['name']??'', ['maintenance','email_enabled','push_enabled','notice'])) throw new Problem(422,'Cấu hình không được hỗ trợ.');
            $row=['name'=>$v['name'],'value'=>json_encode($v['value']??null)];$old=$this->db->table('settings')->where('name',$v['name'])->get()->getRowArray();
            $old?$this->db->table('settings')->where('id',$old['id'])->update($row):$this->db->table('settings')->insert($row);return ['saved'=>true];
        }
        if ($method==='get'&&in_array($r,['tenants','plans','invoices','payments','subscriptions'])) return ['data'=>$this->db->table($r)->orderBy('id','DESC')->limit(200)->get()->getResultArray()];
        throw new Problem(404,'Không tìm thấy thao tác quản trị.');
    }
}
