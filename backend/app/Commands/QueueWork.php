<?php
namespace App\Commands;
use CodeIgniter\CLI\{BaseCommand,CLI};
use App\Domain\{Store,RedisGateway};
final class QueueWork extends BaseCommand
{
    protected $group='MiniHRM';protected $name='hrm:work';protected $description='Process durable notification outbox once (cron-safe).';
    public function run(array $params)
    {
        $db=db_connect();$now=time();$processed=0;
        // Redis lease coordinates workers; SQL row locks still protect claims if Redis fails.
        $redis=new RedisGateway();try { $lease=$redis->command(['SET','hrm:worker',getmypid(),'NX','EX',60]);if ($lease===null&&env('HRM_REDIS_HOST')) return; } catch(\Throwable $e) { log_message('warning','Redis lease unavailable; using database claims.'); }
        for($i=0;$i<50;$i++) {
            try{$redis->command(['RPOP','hrm:notifications']);}catch(\Throwable){}
            $db->transBegin();$q='SELECT * FROM jobs WHERE completed_at = 0 AND attempts < 5 AND available_at <= ? AND (reserved_at = 0 OR reserved_at < ?) ORDER BY id LIMIT 1';if($db->DBDriver==='MySQLi')$q.=' FOR UPDATE SKIP LOCKED';
            $job=$db->query($q,[$now,$now-300])->getRowArray();if(!$job){$db->transCommit();break;}
            $db->table('jobs')->where('id',$job['id'])->update(['reserved_at'=>$now,'attempts'=>(int)$job['attempts']+1]);$db->transCommit();
            try {
                $p=json_decode($job['payload'],true,512,JSON_THROW_ON_ERROR);$user=$db->table('users')->where('id',$p['user_id'])->where('active',1)->get()->getRowArray();
                if(!$user)throw new \RuntimeException('Recipient unavailable');
                if($job['channel']==='email') {
                    if(!env('email.SMTPHost'))throw new \RuntimeException('SMTP not configured; retained for retry');
                    $email=service('email');$email->clear();$email->setTo($user['email']);$email->setSubject($p['title']);$email->setMessage($p['body']);
                    $email->setHeader('Message-ID','<'.hash('sha256',$job['event_key']).'@minihrm.local>');if(!$email->send(false))throw new \RuntimeException('SMTP delivery failed');
                } else {
                    $devices=$db->table('push_devices')->where('tenant_id',$job['tenant_id'])->where('user_id',$user['id'])->get()->getResultArray();
                    if(!$devices)throw new \RuntimeException('No push device registered; in-app notification available');
                    foreach($devices as $device)(new \App\Domain\FirebasePush())->send($device['token'],$p['title'],$p['body'],$job['event_key']);
                }
                $db->table('jobs')->where('id',$job['id'])->update(['completed_at'=>time(),'reserved_at'=>0,'last_error'=>null]);$processed++;
            } catch(\Throwable $e) { $db->table('jobs')->where('id',$job['id'])->update(['reserved_at'=>0,'available_at'=>time()+min(86400,60*(2**(int)$job['attempts'])),'last_error'=>substr($e->getMessage(),0,250)]); }
        }
        CLI::write('Delivered '.$processed.' queued notifications.');
    }
}
