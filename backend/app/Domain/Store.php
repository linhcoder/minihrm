<?php
namespace App\Domain;
use CodeIgniter\Database\BaseConnection;
final class Store
{
    public function __construct(public BaseConnection $db,public Context $ctx) {}
    public static function now(): string { return gmdate('Y-m-d\TH:i:s\Z'); }
    public function query(string $resource)
    {
        $q=$this->db->table(Schema::table($resource))->where('tenant_id',$this->ctx->tenantId())->where('deleted_at',null);
        if ($this->ctx->isEmployee()) {
            if (in_array($resource,['notifications','privacy-requests'])) $q->where('user_id',$this->ctx->user['id']);
            elseif (array_key_exists('employee_id',Schema::RESOURCES[$resource]??[])) $q->where('employee_id',$this->ctx->membership['employee_id']??-1);
            elseif ($resource==='employees') $q->where('id',$this->ctx->membership['employee_id']??-1);
            elseif (!in_array($resource,['shifts','leave-types','holidays','subscriptions'])) throw new Problem(403,'Tài nguyên không dành cho nhân viên.');
            if ($resource==='payslips') $q->where('status','published');
        }
        $branches=$this->ctx->branches();
        if ($branches!==null) {
            if ($resource==='branches') $q->whereIn('id',$branches?:[-1]);
            elseif (array_key_exists('branch_id',Schema::RESOURCES[$resource]??[])) $q->whereIn('branch_id',$branches?:[-1]);
            elseif (array_key_exists('employee_id',Schema::RESOURCES[$resource]??[])) {
                $ids=array_column($this->db->table('employees')->select('id')->where('tenant_id',$this->ctx->tenantId())->whereIn('branch_id',$branches?:[-1])->get()->getResultArray(),'id');
                $q->whereIn('employee_id',$ids?:[-1]);
            }
        }
        return $q;
    }
    public function find(string $r,int $id): array { $row=$this->query($r)->where('id',$id)->get()->getRowArray(); if (!$row) throw new Problem(404,'Không tìm thấy dữ liệu.'); return $row; }
    public function insert(string $r,array $v): array
    {
        $v['tenant_id']=$this->ctx->tenantId(); $v['created_at']=self::now(); $v['updated_at']=self::now();
        if(!$this->db->table(Schema::table($r))->insert($v))throw new Problem(409,'Dữ liệu trùng lặp hoặc tham chiếu không hợp lệ.'); $id=(int)$this->db->insertID(); $this->audit('create',$r,$id,null,$v);
        return $this->db->table(Schema::table($r))->where('id',$id)->get()->getRowArray();
    }
    public function update(string $r,int $id,array $v): array
    {
        $before=$this->find($r,$id); $v['updated_at']=self::now(); $this->query($r)->where('id',$id)->update($v);
        $this->audit('update',$r,$id,$before,$v); return array_merge($before,$v);
    }
    public function audit(string $action,string $r,int $id,?array $before,?array $after): void
    {
        foreach (['before','after'] as $var) if ($$var) foreach (['password_hash','emergency_contact'] as $k) unset(${$var}[$k]);
        $this->db->table('audit_logs')->insert(['tenant_id'=>$this->ctx->tenantId(),'actor_id'=>$this->ctx->user['id'],'action'=>$action,'resource'=>$r,'record_id'=>$id,'before_value'=>$before?json_encode($before):null,'after_value'=>$after?json_encode($after):null,'created_at'=>self::now()]);
    }
    public function transaction(callable $fn): mixed
    {
        $this->db->transBegin(); try { $out=$fn(); if (!$this->db->transStatus()) throw new Problem(409,'Xung đột dữ liệu.'); $this->db->transCommit(); return $out; } catch (\Throwable $e) { $this->db->transRollback(); $this->db->resetTransStatus();throw $e; }
    }
    public function lockTenant(): void { if ($this->db->DBDriver==='MySQLi') $this->db->query('SELECT id FROM tenants WHERE id = ? FOR UPDATE',[$this->ctx->tenantId()]); }
}
