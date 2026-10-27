<?php
namespace App\Domain;
final class Context
{
    public function __construct(public array $user,public ?array $membership=null,public ?array $tenant=null) {}
    public function tenantId(): int { if (!$this->tenant) throw new Problem(403,'Cần tài khoản công ty.'); return (int)$this->tenant['id']; }
    public function require(string $permission): void
    {
        $role=$this->membership['role']??''; $grants=json_decode($this->membership['permissions']??'[]',true)??[];
        if ($role==='owner'||in_array($permission,$grants,true)) return;
        $defaults=['admin'=>['people','schedule','attendance','leave','reports','settings','invite'],'manager'=>['schedule','attendance','leave','reports'],'employee'=>['self']];
        if (!in_array($permission,$defaults[$role]??[],true)) throw new Problem(403,'Bạn không có quyền thực hiện thao tác này.');
    }
    public function isEmployee(): bool { return ($this->membership['role']??'')==='employee'; }
    public function branches(): ?array
    {
        if (($this->membership['role']??'')!=='manager') return null;
        return array_map('intval',json_decode($this->membership['branch_ids']??'[]',true)??[]);
    }
}
