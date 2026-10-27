<?php
namespace App\Commands;
use CodeIgniter\CLI\{BaseCommand,CLI};
use App\Domain\Store;
final class CreateAdmin extends BaseCommand
{
    protected $group='MiniHRM';protected $name='hrm:admin';protected $description='Create a platform admin using HRM_ADMIN_EMAIL and HRM_ADMIN_PASSWORD environment variables.';
    public function run(array $params)
    {
        $email=getenv('HRM_ADMIN_EMAIL');$password=getenv('HRM_ADMIN_PASSWORD');
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password?:'')<16)throw new \RuntimeException('Set HRM_ADMIN_EMAIL and HRM_ADMIN_PASSWORD (16+ chars).');
        $db=db_connect();if($db->table('users')->where('email',strtolower($email))->countAllResults())throw new \RuntimeException('Account already exists.');
        $db->table('users')->insert(['email'=>strtolower($email),'full_name'=>'Platform Administrator','password_hash'=>password_hash($password,PASSWORD_DEFAULT),'verified_at'=>Store::now(),'active'=>1,'is_super'=>1,'created_at'=>Store::now()]);CLI::write('Platform administrator created.');
    }
}
