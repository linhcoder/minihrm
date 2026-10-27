<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
final class PushDevices extends Migration
{
    public function up(){
        $this->forge->addField(['id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true],'tenant_id'=>['type'=>'INT','unsigned'=>true],'user_id'=>['type'=>'INT','unsigned'=>true],'token'=>['type'=>'VARCHAR','constraint'=>512],'token_hash'=>['type'=>'VARCHAR','constraint'=>64],'created_at'=>['type'=>'VARCHAR','constraint'=>30]]);
        $this->forge->addKey('id',true);$this->forge->addUniqueKey('token_hash');$this->forge->addForeignKey('tenant_id','tenants','id','CASCADE','RESTRICT');$this->forge->addForeignKey('user_id','users','id','CASCADE','RESTRICT');$this->forge->createTable('push_devices',true);
    }
    public function down(){throw new \RuntimeException('Restore a reviewed backup instead.');}
}
