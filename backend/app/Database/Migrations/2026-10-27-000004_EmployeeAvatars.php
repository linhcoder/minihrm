<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
final class EmployeeAvatars extends Migration
{
    public function up(){
        if($this->db->fieldExists('avatar_file_id','employees'))return;
        $this->forge->addColumn('employees',['avatar_file_id'=>['type'=>'INT','unsigned'=>true,'null'=>true]]);
        if($this->db->DBDriver==='MySQLi')$this->db->query('ALTER TABLE employees ADD CONSTRAINT fk_employees_avatar_file_id FOREIGN KEY (tenant_id, avatar_file_id) REFERENCES files(tenant_id,id) ON DELETE RESTRICT');
    }
    public function down(){throw new \RuntimeException('Restore a reviewed backup instead.');}
}
