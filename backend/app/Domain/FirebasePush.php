<?php
namespace App\Domain;
interface PushProvider { public function send(string $token,string $title,string $body,string $event): void; }
final class FirebasePush implements PushProvider
{
    private function request(string $url,array|string $payload,array $headers=[]): array
    {
        $ch=curl_init($url);$encoded=is_array($payload)?http_build_query($payload):$payload;
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$encoded,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>$headers]);
        $response=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        if($response===false||$status<200||$status>=300)throw new \RuntimeException('FCM/OAuth request failed ('.$status.').');return json_decode($response,true,512,JSON_THROW_ON_ERROR);
    }
    public function send(string $token,string $title,string $body,string $event): void
    {
        $path=env('HRM_FCM_SERVICE_ACCOUNT');if(!$path||!is_file($path))throw new \RuntimeException('FCM service account not configured; in-app notification available');
        $config=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        foreach(['client_email','private_key','project_id'] as $key)if(empty($config[$key]))throw new \RuntimeException('Invalid FCM service account.');
        $base64=fn($s)=>rtrim(strtr(base64_encode($s),'+/','-_'),'=');$now=time();
        $jwt=$base64(json_encode(['alg'=>'RS256','typ'=>'JWT'])).'.'.$base64(json_encode(['iss'=>$config['client_email'],'scope'=>'https://www.googleapis.com/auth/firebase.messaging','aud'=>'https://oauth2.googleapis.com/token','iat'=>$now,'exp'=>$now+3500]));
        if(!openssl_sign($jwt,$signature,$config['private_key'],OPENSSL_ALGO_SHA256))throw new \RuntimeException('FCM signing failed.');
        $access=$this->request('https://oauth2.googleapis.com/token',['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt.'.'.$base64($signature)])['access_token'];
        // Push is intentionally generic; salary, leave reasons and coordinates never leave in payloads.
        $payload=json_encode(['message'=>['token'=>$token,'notification'=>['title'=>'Mini HRM','body'=>'Bạn có cập nhật mới. Mở ứng dụng để xem.'],'data'=>['event_id'=>hash('sha256',$event)]]]);
        $this->request('https://fcm.googleapis.com/v1/projects/'.rawurlencode($config['project_id']).'/messages:send',$payload,['Content-Type: application/json','Authorization: Bearer '.$access]);
    }
}
