<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
final class GuidancePreferences extends Migration
{
    public function up(){
        $this->forge->addField([
            'id'=>['type'=>'INT','unsigned'=>true,'auto_increment'=>true],
            'user_id'=>['type'=>'INT','unsigned'=>true],
            'scope'=>['type'=>'VARCHAR','constraint'=>100],
            'version'=>['type'=>'INT','default'=>1],
            'status'=>['type'=>'VARCHAR','constraint'=>20],
            'updated_at'=>['type'=>'VARCHAR','constraint'=>30],
        ]);
        $this->forge->addKey('id',true);$this->forge->addUniqueKey(['user_id','scope']);
        $this->forge->addForeignKey('user_id','users','id','CASCADE','RESTRICT');
        $this->forge->createTable('guidance_preferences',true);
    }
    public function down(){throw new \RuntimeException('Restore a reviewed backup instead.');}
}
