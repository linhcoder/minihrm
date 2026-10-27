<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;
final class PlatformSeeder extends Seeder
{
    public function run()
    {
        $plans=[['Free',0,5,['attendance','timesheets']],['Starter',99000,10,['attendance','timesheets','payroll','export']],['Business',199000,30,['attendance','timesheets','payroll','export','advanced_scheduling','gps','leave']],['Pro',399000,60,['attendance','timesheets','payroll','export','advanced_scheduling','gps','leave','branches','advanced_reports']]];
        foreach($plans as [$name,$price,$limit,$features]) if (!$this->db->table('plans')->where('name',$name)->countAllResults()) $this->db->table('plans')->insert(['name'=>$name,'price'=>$price,'employee_limit'=>$limit,'features'=>json_encode($features)]);
        foreach (['people','schedule','attendance','leave','reports','settings','invite','payroll','self'] as $name) if (!$this->db->table('permissions')->where('name',$name)->countAllResults()) $this->db->table('permissions')->insert(['name'=>$name]);
        foreach (['owner'=>['*'],'admin'=>['people','schedule','attendance','leave','reports','settings','invite'],'manager'=>['schedule','attendance','leave','reports'],'employee'=>['self']] as $name=>$permissions) if (!$this->db->table('roles')->where('name',$name)->countAllResults()) $this->db->table('roles')->insert(['name'=>$name,'permissions'=>json_encode($permissions)]);
    }
}
