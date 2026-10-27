<?php
namespace App\Domain;
final class Subscriptions
{
    public function __construct(private Store $s) {}
    public function current(): array
    {
        $sub=$this->s->query('subscriptions')->get()->getRowArray();
        if (!$sub) throw new Problem(403,'Chưa cấu hình gói dịch vụ.');
        $plan=$this->s->db->table('plans')->where('id',$sub['plan_id'])->get()->getRowArray();
        $expired=$sub['ends_at']&&$sub['ends_at']<gmdate('Y-m-d')&&(!$sub['grace_until']||$sub['grace_until']<gmdate('Y-m-d'));
        return [...$sub,'plan'=>$plan,'read_only'=>$expired||in_array($sub['status'],['expired','cancelled']),'features'=>json_decode($plan['features'],true)];
    }
    public function writable(?string $feature=null): void
    {
        $sub=$this->current();
        if ($sub['read_only']) throw new Problem(402,'Gói đã hết hạn. Dữ liệu vẫn được giữ ở chế độ chỉ đọc.');
        if ($feature&&!in_array($feature,$sub['features'],true)) throw new Problem(402,'Tính năng cần nâng cấp gói dịch vụ.');
    }
    public function employeeLimit(): void
    {
        $this->writable(); $sub=$this->current();
        $limit=$sub['employee_limit']?:$sub['plan']['employee_limit'];
        if ($this->s->query('employees')->where('status !=','inactive')->countAllResults()>=(int)$limit) throw new Problem(402,'Đã đạt giới hạn nhân viên của gói.');
    }
}
