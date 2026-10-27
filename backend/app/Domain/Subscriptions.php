<?php
namespace App\Domain;
final class Subscriptions
{
    public const FEATURES=['attendance','timesheets','payroll','export','advanced_scheduling','gps','leave','branches','advanced_reports'];
    public function __construct(private Store $s) {}
    public function current(): array
    {
        $sub=$this->s->query('subscriptions')->get()->getRowArray();
        // Legacy subscriptions remain in storage for historical records, never for access decisions.
        return [...($sub??[]),'status'=>'active','ends_at'=>null,'grace_until'=>null,'employee_limit'=>null,'read_only'=>false,'features'=>self::FEATURES,'plan'=>['name'=>'Đầy đủ tính năng','price'=>0,'employee_limit'=>null,'features'=>json_encode(self::FEATURES)]];
    }
    public function writable(?string $feature=null): void
    {
        $this->s->ctx->tenantId();
    }
    public function employeeLimit(): void
    {
        $this->s->ctx->tenantId(); // No commercial cap; tenant and personnel permissions still apply.
    }
}
