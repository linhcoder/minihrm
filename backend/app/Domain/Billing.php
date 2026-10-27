<?php
namespace App\Domain;
use CodeIgniter\Database\BaseConnection;
interface PaymentProvider { public function verify(string $body,string $signature): array; }
final class SignedPaymentProvider implements PaymentProvider
{
    public function verify(string $body,string $signature): array
    {
        $secret=env('HRM_PAYMENT_WEBHOOK_SECRET');
        if (!$secret||strlen($secret)<32) throw new Problem(503,'Chưa cấu hình nhà cung cấp thanh toán.');
        if (!hash_equals(hash_hmac('sha256',$body,$secret),$signature)) throw new Problem(401,'Chữ ký thanh toán không hợp lệ.');
        $v=json_decode($body,true,512,JSON_THROW_ON_ERROR);
        if (abs(time()-(int)($v['timestamp']??0))>300||($v['status']??'')!=='settled') throw new Problem(422,'Thông báo thanh toán không hợp lệ.');
        return $v;
    }
}
final class Billing
{
    public function __construct(private BaseConnection $db,private ?Store $s=null) {}
    public function webhook(string $body,string $signature): bool
    {
        $v=(new SignedPaymentProvider())->verify($body,$signature);
        $this->settle((int)($v['invoice_id']??0),(int)($v['amount']??0),'signed_adapter',(string)($v['reference']??''));return true;
    }
    public function settle(int $invoice,int $amount,string $provider,string $reference,?int $actor=null): array
    {
        if (!$reference||strlen($reference)>120) throw new Problem(422,'Cần mã tham chiếu thanh toán.');
        $this->db->transBegin();
        try {
            $q='SELECT * FROM invoices WHERE id = ?';if ($this->db->DBDriver==='MySQLi') $q.=' FOR UPDATE';
            $i=$this->db->query($q,[$invoice])->getRowArray();if (!$i) throw new Problem(404,'Không tìm thấy hóa đơn.');
            $old=$this->db->table('payments')->where('provider',$provider)->where('reference',$reference)->get()->getRowArray();
            if ($old) { if ((int)$old['invoice_id']!==$invoice||(int)$old['amount']!==$amount) throw new Problem(409,'Mã thanh toán đã dùng.');$this->db->transCommit();return $old; }
            if ($i['status']==='paid'||$amount!==(int)$i['amount']) throw new Problem(409,'Số tiền không khớp hoặc hóa đơn đã thanh toán.');
            $p=['tenant_id'=>$i['tenant_id'],'invoice_id'=>$invoice,'amount'=>$amount,'provider'=>$provider,'reference'=>$reference,'verified_by'=>$actor,'created_at'=>Store::now()];
            $this->db->table('payments')->insert($p);
            $this->db->table('invoices')->where('id',$invoice)->update(['status'=>'paid','updated_at'=>Store::now()]);
            $values=['status'=>'active','ends_at'=>$i['period_end'],'grace_until'=>null,'updated_at'=>Store::now(),'cancel_at_end'=>0];
            if ($i['plan_id']) $values+=['plan_id'=>$i['plan_id'],'employee_limit'=>0];
            $this->db->table('subscriptions')->where('id',$i['subscription_id'])->where('tenant_id',$i['tenant_id'])->update($values);
            $this->db->transCommit();return $p;
        } catch(\Throwable $e) { $this->db->transRollback();throw $e; }
    }
    public function company(string $path,string $method,array $v): array
    {
        $s=$this->s;$s->ctx->require('billing');
        if ($method==='get') return ['invoices'=>$s->query('invoices')->orderBy('id','DESC')->get()->getResultArray(),'payments'=>$s->query('payments')->orderBy('id','DESC')->get()->getResultArray(),'subscription'=>(new Subscriptions($s))->current()];
        throw new Problem(410,'Tất cả tính năng đã được mở. Không cần đăng ký, gia hạn hoặc thanh toán gói.');
        return $s->transaction(function() use($s,$path,$v) {
            $s->lockTenant();$sub=(new Subscriptions($s))->current();
            if ($path==='cancel') return $s->update('subscriptions',(int)$sub['id'],['cancel_at_end'=>1]);
            if ($path==='renew'||$path==='change-plan') {
                $plan=$this->db->table('plans')->where('id',(int)($v['plan_id']??$sub['plan_id']))->get()->getRowArray();if (!$plan) throw new Problem(422,'Gói không hợp lệ.');
                $count=$s->query('employees')->where('status','active')->countAllResults();if ($count>(int)$plan['employee_limit']) throw new Problem(409,'Số nhân viên vượt giới hạn gói mới.');
                if ($s->query('invoices')->where('status','pending')->countAllResults()) throw new Problem(409,'Hãy hoàn tất hoặc hủy hóa đơn đang chờ.');
                // Plan activates only when verified by a server-side payment or manual review.
                if ((int)$plan['price']===0) return $s->update('subscriptions',(int)$sub['id'],['plan_id'=>$plan['id'],'status'=>'active','ends_at'=>null,'employee_limit'=>0,'cancel_at_end'=>0]);
                // Preserve current entitlements until payment; pending plan is held on invoice.
                return $s->insert('invoices',['subscription_id'=>$sub['id'],'plan_id'=>$plan['id'],'amount'=>$plan['price'],'status'=>'pending','due_date'=>gmdate('Y-m-d',time()+7*86400),'period_end'=>(new \DateTimeImmutable(max(gmdate('Y-m-d'),$sub['ends_at']??'')))->modify('+1 month')->format('Y-m-d')]);
            }
            throw new Problem(404,'Không tìm thấy thao tác thanh toán.');
        });
    }
}
