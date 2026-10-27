<?php
namespace App\Domain;
final class Notifications
{
    public function __construct(private Store $s) {}
    public function send(int $user,string $key,string $title,string $body): void
    {
        if ($this->s->query('notifications')->where('user_id',$user)->where('event_key',$key)->countAllResults()) return;
        $this->s->insert('notifications',['user_id'=>$user,'event_key'=>$key,'title'=>$title,'body'=>$body]);
        foreach (['email','push'] as $channel) {
            $job=$this->s->insert('jobs',['event_key'=>$key.':'.$user,'channel'=>$channel,'payload'=>json_encode(['user_id'=>$user,'title'=>$title,'body'=>$body]),'available_at'=>time()]);
            try{(new RedisGateway())->command(['LPUSH','hrm:notifications',$job['id']]);}catch(\Throwable){/* SQL outbox remains authoritative after rollback or Redis loss. */}
        }
    }
    public function managers(string $key,string $title,string $body): void
    {
        $members=$this->s->db->table('tenant_memberships')->where('tenant_id',$this->s->ctx->tenantId())->whereIn('role',['owner','admin','manager'])->where('deleted_at',null)->get()->getResultArray();
        foreach ($members as $m) $this->send((int)$m['user_id'],$key,$title,$body);
    }
}
