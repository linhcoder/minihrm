<?php
namespace App\Commands;
use CodeIgniter\CLI\{BaseCommand,CLI};
final class MailWork extends BaseCommand
{
    protected $group='MiniHRM';protected $name='hrm:mail';protected $description='Deliver auth verification/invitation spool using configured SMTP.';
    public function run(array $params)
    {
        if(!env('email.SMTPHost')){CLI::write('SMTP not configured. Auth links remain in private writable/mail for development.');return;}
        $count=0;foreach(array_slice(glob(WRITEPATH.'mail/*.json')?:[],0,50) as $path){
            $handle=fopen($path,'r+');if(!$handle||!flock($handle,LOCK_EX|LOCK_NB))continue;
            try{$p=json_decode(stream_get_contents($handle),true,512,JSON_THROW_ON_ERROR);$email=service('email');$email->clear();$email->setTo($p['to']);$email->setSubject($p['subject']);$email->setMessage($p['body']);if($email->send(false)){ftruncate($handle,0);$count++;}}catch(\Throwable $e){log_message('error','Auth email delivery failed.');}finally{flock($handle,LOCK_UN);fclose($handle);if(filesize($path)===0)unlink($path);}
        }CLI::write('Delivered '.$count.' auth messages.');
    }
}
