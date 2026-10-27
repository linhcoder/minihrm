<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
use App\Domain\Schema;
final class AddIntegrity extends Migration
{
    public function up()
    {
        $this->forge->addColumn('invoices',['plan_id'=>['type'=>'INT','unsigned'=>true,'null'=>true]]);
        if ($this->db->DBDriver!=='MySQLi') return;
        // Same-tenant composite foreign keys defend even direct database writes.
        foreach (array_keys(Schema::RESOURCES) as $r) {
            $table=Schema::table($r);$this->db->query("ALTER TABLE `$table` ADD UNIQUE KEY `{$table}_tenant_object` (`tenant_id`,`id`)");
        }
        foreach(Schema::RESOURCES as $r=>$columns) foreach($columns as $column=>$type) {
            $type=rtrim($type,'!');if (!str_starts_with($type,'ref:')) continue;$ref=substr($type,4);$table=Schema::table($r);
            if (!isset(Schema::RESOURCES[$ref])) continue;
            $target=Schema::table($ref);$constraint=substr('fk_'.$table.'_'.$column,0,60);
            $this->db->query("ALTER TABLE `$table` ADD CONSTRAINT `$constraint` FOREIGN KEY (`tenant_id`,`$column`) REFERENCES `$target` (`tenant_id`,`id`) ON DELETE RESTRICT ON UPDATE RESTRICT");
        }
        foreach(['tenant_memberships','auth_tokens','auth_challenges'] as $table) $this->db->query("ALTER TABLE `$table` ADD CONSTRAINT `fk_{$table}_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT");
        $this->db->query('ALTER TABLE subscriptions ADD CONSTRAINT fk_subscription_plan FOREIGN KEY (plan_id) REFERENCES plans(id)');
        $this->db->query('ALTER TABLE invoices ADD CONSTRAINT fk_invoice_plan FOREIGN KEY (plan_id) REFERENCES plans(id)');
    }
    public function down() { throw new \RuntimeException('Restore a reviewed backup instead.'); }
}
