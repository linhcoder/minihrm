<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
use App\Domain\Schema;
final class CreatePlatform extends Migration
{
    private function create(string $name,array $columns,array $unique=[],bool $tenant=false): void
    {
        $fields=['id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true]];
        if ($tenant) $fields['tenant_id']=['type'=>'INT','unsigned'=>true];
        foreach ($columns as $key=>$type) {
            $required=str_ends_with($type,'!'); $type=rtrim($type,'!');
            $fields[$key]=match(true) {
                str_starts_with($type,'ref:')=>['type'=>'INT','unsigned'=>true,'null'=>!$required],
                in_array($type,['int','money'])=>['type'=>'BIGINT','default'=>0],
                $type==='decimal'=>['type'=>'DECIMAL','constraint'=>'12,6','null'=>true],
                in_array($type,['json','secret'])=>['type'=>'TEXT','null'=>!$required],
                default=>['type'=>'VARCHAR','constraint'=>255,'null'=>!$required],
            };
        }
        foreach (['created_at','updated_at','deleted_at'] as $k) $fields[$k]=['type'=>'VARCHAR','constraint'=>30,'null'=>true];
        $this->forge->addField($fields); $this->forge->addKey('id',true);
        if ($tenant) { $this->forge->addKey(['tenant_id','deleted_at']); $this->forge->addForeignKey('tenant_id','tenants','id','CASCADE','RESTRICT'); }
        foreach ($unique as $keys) $this->forge->addUniqueKey($keys);
        $this->forge->createTable($name,true);
    }
    public function up()
    {
        $this->create('plans',['name'=>'text!','price'=>'money','employee_limit'=>'int','features'=>'json!'],[['name']]);
        $this->create('tenants',['name'=>'text!','business_type'=>'text','timezone'=>'text!','working_days'=>'json','payroll_day'=>'int','onboarded'=>'int','status'=>'text!','retention_days'=>'int']);
        $this->create('users',['email'=>'text!','full_name'=>'text!','password_hash'=>'text!','verified_at'=>'text','is_super'=>'int','active'=>'int'],[['email']]);
        $this->create('tenant_memberships',['user_id'=>'ref:users!','role'=>'text!','branch_ids'=>'json','permissions'=>'json','employee_id'=>'int'],[['tenant_id','user_id']],true);
        $this->create('roles',['name'=>'text!','permissions'=>'json!'],[['name']]);
        $this->create('permissions',['name'=>'text!'],[['name']]);
        $this->create('auth_tokens',['user_id'=>'ref:users!','access_hash'=>'text!','refresh_hash'=>'text!','access_expires'=>'int','refresh_expires'=>'int','revoked'=>'int'],[['access_hash'],['refresh_hash']]);
        $this->create('auth_challenges',['user_id'=>'ref:users!','kind'=>'text!','token_hash'=>'text!','expires'=>'int','used'=>'int'],[['token_hash']]);
        $this->create('login_attempts',['identity'=>'text!','attempted_at'=>'int']);
        $this->create('subscriptions',['plan_id'=>'ref:plans!','status'=>'text!','starts_at'=>'date!','ends_at'=>'date','grace_until'=>'date','employee_limit'=>'int','cancel_at_end'=>'int'],[['tenant_id']],true);
        $this->create('invoices',['subscription_id'=>'ref:subscriptions!','amount'=>'money!','status'=>'text!','due_date'=>'date!','period_end'=>'date!'],[],true);
        $this->create('payments',['invoice_id'=>'ref:invoices!','amount'=>'money!','provider'=>'text!','reference'=>'text!','verified_by'=>'ref:users'],[['provider','reference']],true);
        foreach (Schema::RESOURCES as $r=>$cols) {
            $unique=match($r) {
                'employees'=>[['tenant_id','code']], 'attendance'=>[['tenant_id','idempotency_key']],
                'timesheets'=>[['tenant_id','schedule_id']], 'schedules'=>[['tenant_id','employee_id','shift_id','work_date']],
                'payslips'=>[['tenant_id','employee_id','payroll_period_id']], 'notifications'=>[['tenant_id','event_key','user_id']], default=>[]
            };
            $this->create(Schema::table($r),$cols,$unique,true);
        }
        $this->create('audit_logs',['actor_id'=>'ref:users','action'=>'text!','resource'=>'text!','record_id'=>'int','before_value'=>'json','after_value'=>'json','ip'=>'text'],[],true);
        $this->create('platform_audit',['actor_id'=>'ref:users!','action'=>'text!','payload'=>'json!']);
        $this->create('settings',['name'=>'text!','value'=>'json!'],[['name']]);
        $this->create('jobs',['event_key'=>'text!','channel'=>'text!','payload'=>'json!','attempts'=>'int','available_at'=>'int','reserved_at'=>'int','completed_at'=>'int','last_error'=>'text'],[['event_key','channel']],true);
        $this->create('idempotency',['user_id'=>'ref:users!','request_key'=>'text!','request_hash'=>'text!','response'=>'json!'],[['tenant_id','user_id','request_key']],true);
    }
    public function down() { throw new \RuntimeException('Destructive rollback requires a reviewed restore plan.'); }
}
