<?php
namespace App\Domain;
final class Validation
{
    public function __construct(private Store $s) {}
    public function clean(string $r,array $input,bool $partial=false): array
    {
        $out=[]; $errors=[];
        foreach (Schema::RESOURCES[$r] as $key=>$type) {
            $required=str_ends_with($type,'!'); $type=rtrim($type,'!');
            if (!array_key_exists($key,$input)||$input[$key]===''||$input[$key]===null) {
                if ($required&&!$partial) $errors[$key]='Trường bắt buộc.';
                elseif (array_key_exists($key,$input)) $out[$key]=in_array($type,['int','money'])?0:null;
                continue;
            }
            $v=$input[$key];
            if (str_starts_with($type,'ref:')) {
                if (!ctype_digit((string)$v)||(int)$v<1) { $errors[$key]='ID không hợp lệ.'; continue; }
                $ref=substr($type,4);
                if ($ref==='users') {
                    if (!$this->s->db->table('tenant_memberships')->where('tenant_id',$this->s->ctx->tenantId())->where('user_id',$v)->where('deleted_at',null)->countAllResults()) throw new Problem(404,'Không tìm thấy tài khoản trong công ty.');
                } else $this->s->find($ref,(int)$v);
                $v=(int)$v;
            } elseif (in_array($type,['int','money'])) {
                if (!preg_match('/^\d{1,12}$/',(string)$v)) { $errors[$key]='Phải là số nguyên không âm (tối đa 12 chữ số).'; continue; } $v=(int)$v;
            } elseif ($type==='date') {
                $d=\DateTimeImmutable::createFromFormat('!Y-m-d',(string)$v);
                if (!$d||$d->format('Y-m-d')!==$v) { $errors[$key]='Ngày phải có dạng YYYY-MM-DD.'; continue; }
            } elseif ($type==='time'&&!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/',(string)$v)) { $errors[$key]='Giờ phải có dạng HH:mm.'; continue;
            } elseif ($type==='email'&&!filter_var($v,FILTER_VALIDATE_EMAIL)) { $errors[$key]='Email không hợp lệ.'; continue;
            } elseif ($type==='decimal') {
                if (!is_numeric($v)) { $errors[$key]='Số không hợp lệ.'; continue; }
                $v=(string)$v;
            } elseif ($type==='json') {
                if (is_string($v)) { $parsed=json_decode($v,true); if (json_last_error()) { $errors[$key]='JSON không hợp lệ.'; continue; } $v=$parsed; }
                $v=json_encode($v,JSON_THROW_ON_ERROR);
            } elseif ($type==='secret') {
                $hex=env('HRM_ENCRYPTION_KEY');
                if (!$hex||strlen($hex)!==64) throw new Problem(503,'Cần cấu hình khóa mã hóa thông tin nhạy cảm.');
                $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt((string)$v,'aes-256-gcm',hex2bin($hex),OPENSSL_RAW_DATA,$iv,$tag);
                $v=base64_encode($iv.$tag.$cipher);
            } elseif (!is_scalar($v)||mb_strlen((string)$v)>255) { $errors[$key]='Tối đa 255 ký tự.'; continue; }
            $out[$key]=$v;
        }
        if ($errors) throw new Problem(422,'Vui lòng kiểm tra dữ liệu.',$errors);
        foreach (['start_date'=>'end_date','effective_from'=>'effective_to'] as $a=>$b) if (!empty($out[$a])&&!empty($out[$b])&&$out[$a]>$out[$b]) throw new Problem(422,'Ngày kết thúc phải sau ngày bắt đầu.');
        if (isset($out['timezone'])&&!in_array($out['timezone'],\DateTimeZone::listIdentifiers())) throw new Problem(422,'Múi giờ không hợp lệ.');
        return $out;
    }
}
