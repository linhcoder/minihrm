<?php
namespace App\Domain;
use CodeIgniter\Database\BaseConnection;
final class Auth
{
    public function __construct(private BaseConnection $db) {}
    private function token(array $user): array
    {
        $a=bin2hex(random_bytes(32)); $r=bin2hex(random_bytes(48));
        $this->db->table('auth_tokens')->insert(['user_id'=>$user['id'],'access_hash'=>hash('sha256',$a),'refresh_hash'=>hash('sha256',$r),'access_expires'=>time()+900,'refresh_expires'=>time()+2592000,'revoked'=>0,'created_at'=>Store::now()]);
        unset($user['password_hash']);
        return ['access_token'=>$a,'refresh_token'=>$r,'expires_in'=>900,'user'=>$user];
    }
    public function login(array $v,string $ip): array
    {
        $email=strtolower(trim($v['email']??'')); $identity=hash('sha256',$email.'|'.$ip);
        $n=$this->db->table('login_attempts')->where('identity',$identity)->where('attempted_at >',time()-900)->countAllResults();
        if ($n>=8) throw new Problem(429,'Thử đăng nhập quá nhiều lần. Vui lòng đợi 15 phút.');
        $this->db->table('login_attempts')->insert(['identity'=>$identity,'attempted_at'=>time()]);
        $u=$this->db->table('users')->where('email',$email)->where('active',1)->get()->getRowArray();
        if (!$u||!password_verify($v['password']??'', $u['password_hash'])) throw new Problem(401,'Email hoặc mật khẩu không đúng.');
        if (!$u['verified_at']) throw new Problem(403,'Vui lòng xác minh email.');
        $this->db->table('login_attempts')->where('identity',$identity)->delete(); return $this->token($u);
    }
    public function refresh(string $refresh): array
    {
        $this->db->transBegin();
        try {
            $hash=hash('sha256',$refresh);
            $q='SELECT * FROM auth_tokens WHERE refresh_hash = ?';
            if ($this->db->DBDriver==='MySQLi') $q.=' FOR UPDATE';
            $row=$this->db->query($q,[$hash])->getRowArray();
            if (!$row||$row['revoked']||$row['refresh_expires']<time()) throw new Problem(401,'Phiên đăng nhập đã hết hạn.');
            $u=$this->db->table('users')->where('id',$row['user_id'])->where('active',1)->get()->getRowArray();
            if (!$u) throw new Problem(401,'Tài khoản không hoạt động.');
            $this->db->table('auth_tokens')->where('id',$row['id'])->update(['revoked'=>1]);
            $out=$this->token($u); $this->db->transCommit(); return $out;
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }
    public function context(string $bearer): Context
    {
        $t=$this->db->table('auth_tokens')->where('access_hash',hash('sha256',$bearer))->where('revoked',0)->where('access_expires >',time())->get()->getRowArray();
        if (!$t) throw new Problem(401,'Vui lòng đăng nhập lại.');
        $u=$this->db->table('users')->where('id',$t['user_id'])->where('active',1)->get()->getRowArray();
        if (!$u) throw new Problem(401,'Tài khoản không hoạt động.'); unset($u['password_hash']);
        if ($u['is_super']) return new Context($u);
        $m=$this->db->table('tenant_memberships')->where('user_id',$u['id'])->where('deleted_at',null)->get()->getRowArray();
        $tenant=$m?$this->db->table('tenants')->where('id',$m['tenant_id'])->get()->getRowArray():null;
        if($m&&!empty($m['employee_id'])){
            $employee=$this->db->table('employees')->where('tenant_id',$m['tenant_id'])->where('id',$m['employee_id'])->where('deleted_at',null)->get()->getRowArray();
            if(!$employee||$employee['status']!=='active')throw new Problem(403,'Hồ sơ nhân viên tại công ty đang ngừng hoạt động.');
        }
        if ($tenant&&$tenant['status']==='suspended') throw new Problem(403,'Công ty đang tạm ngừng hoạt động.');
        return new Context($u,$m,$tenant);
    }
    public function logout(string $bearer): void { $this->db->table('auth_tokens')->where('access_hash',hash('sha256',$bearer))->update(['revoked'=>1]); }
    public function register(array $v): array
    {
        if (!filter_var($v['email']??'',FILTER_VALIDATE_EMAIL)||strlen($v['password']??'')<12||!trim($v['full_name']??'')) throw new Problem(422,'Nhập tên, email hợp lệ và mật khẩu tối thiểu 12 ký tự.');
        $this->db->transBegin();
        try {
            $this->db->table('users')->insert(['email'=>strtolower(trim($v['email'])),'full_name'=>$v['full_name'],'password_hash'=>password_hash($v['password'],PASSWORD_DEFAULT),'active'=>1,'created_at'=>Store::now()]);
            $id=(int)$this->db->insertID(); $this->challenge($id,'verify'); $this->db->transCommit();
        } catch (\Throwable $e) { $this->db->transRollback(); if ($e instanceof Problem) throw $e; throw new Problem(409,'Không thể đăng ký email này.'); }
        return ['message'=>'Đã gửi hướng dẫn xác minh email.'];
    }
    public function challenge(int $id,string $kind): void
    {
        $token=bin2hex(random_bytes(32));
        $this->db->table('auth_challenges')->insert(['user_id'=>$id,'kind'=>$kind,'token_hash'=>hash('sha256',$token),'expires'=>time()+3600,'used'=>0]);
        $u=$this->db->table('users')->where('id',$id)->get()->getRowArray();
        $url=rtrim(env('HRM_WEB_URL','http://localhost:5173'),'/').'/'.$kind.'?token='.$token;
        // Development spool stays outside public/. Production worker requires SMTP.
        $payload=['to'=>$u['email'],'subject'=>'Mini HRM: '.$kind,'body'=>'Mở liên kết trong 60 phút: '.$url];
        $dir=WRITEPATH.'mail'; if (!is_dir($dir)) mkdir($dir,0700,true);
        file_put_contents($dir.'/'.bin2hex(random_bytes(12)).'.json',json_encode($payload,JSON_UNESCAPED_UNICODE));
    }
    public function forgot(string $email): array
    {
        $identity=hash('sha256','recovery:'.strtolower(trim($email)));
        $count=$this->db->table('login_attempts')->where('identity',$identity)->where('attempted_at >',time()-900)->countAllResults();
        if($count>=4)return ['message'=>'Nếu email tồn tại, hướng dẫn đã được gửi.'];
        $this->db->table('login_attempts')->insert(['identity'=>$identity,'attempted_at'=>time()]);
        $u=$this->db->table('users')->where('email',strtolower(trim($email)))->get()->getRowArray();
        if ($u) $this->challenge((int)$u['id'],'reset');
        return ['message'=>'Nếu email tồn tại, hướng dẫn đã được gửi.'];
    }
    public function redeem(string $kind,array $v): array
    {
        $this->db->transBegin();
        try {
            $q='SELECT * FROM auth_challenges WHERE token_hash = ? AND kind = ?'; if ($this->db->DBDriver==='MySQLi') $q.=' FOR UPDATE';
            $c=$this->db->query($q,[hash('sha256',$v['token']??''),$kind])->getRowArray();
            if (!$c||$c['used']||$c['expires']<time()) throw new Problem(422,'Liên kết không hợp lệ hoặc đã hết hạn.');
            if ($kind==='reset'&&strlen($v['password']??'')<12) throw new Problem(422,'Mật khẩu phải có ít nhất 12 ký tự.');
            $update=$kind==='verify'?['verified_at'=>Store::now()]:['password_hash'=>password_hash($v['password'],PASSWORD_DEFAULT),'verified_at'=>Store::now()];
            $this->db->table('users')->where('id',$c['user_id'])->update($update);
            $this->db->table('auth_challenges')->where('id',$c['id'])->update(['used'=>1]);
            $this->db->table('auth_tokens')->where('user_id',$c['user_id'])->update(['revoked'=>1]);
            $this->db->transCommit(); return ['message'=>'Đã cập nhật tài khoản. Bạn có thể đăng nhập.'];
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }
}
