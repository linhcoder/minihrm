<?php
namespace App\Domain;
/** Small RESP client; Redis is optional for development, required by production health checks. */
final class RedisGateway
{
    public function command(array $args): mixed
    {
        $host=env('HRM_REDIS_HOST');if (!$host) return null;
        $socket=@stream_socket_client('tcp://'.$host.':'.env('HRM_REDIS_PORT',6379),$errno,$error,1);
        if (!$socket) throw new \RuntimeException('Redis unavailable');stream_set_timeout($socket,2);
        try {
            if ($password=env('HRM_REDIS_PASSWORD')) { fwrite($socket,$this->encode(['AUTH',$password]));$this->read($socket); }
            fwrite($socket,$this->encode($args));return $this->read($socket);
        } finally { fclose($socket); }
    }
    private function encode(array $args): string { $out='*'.count($args)."\r\n";foreach($args as $arg){$arg=(string)$arg;$out.='$'.strlen($arg)."\r\n".$arg."\r\n";}return $out; }
    private function read($socket): mixed
    {
        $line=fgets($socket);if ($line===false) throw new \RuntimeException('Redis timeout');$type=$line[0];$v=rtrim(substr($line,1),"\r\n");
        if ($type==='-') throw new \RuntimeException('Redis command rejected');if ($type===':') return (int)$v;if ($type==='$') { $n=(int)$v;if ($n<0)return null;$body='';while(strlen($body)<$n+2){$chunk=fread($socket,$n+2-strlen($body));if($chunk==='')throw new \RuntimeException('Redis incomplete response');$body.=$chunk;}return substr($body,0,$n); }return $v;
    }
}
