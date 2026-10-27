<?php
namespace App\Domain;
final class Files
{
    public function __construct(private Store $s) {}
    public function upload(array $v): array
    {
        $this->s->ctx->require('people');(new Subscriptions($this->s))->writable();
        $employee=$this->s->find('employees',(int)($v['employee_id']??0));$data=base64_decode($v['content']??'',true);
        if (!$data||strlen($data)>2*1024*1024) throw new Problem(422,'Tệp tối đa 2 MB.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($data);if (!in_array($mime,['image/jpeg','image/png','application/pdf'])) throw new Problem(422,'Chỉ cho phép JPEG, PNG và PDF.');
        if (str_starts_with($mime,'image/')) { $info=getimagesizefromstring($data);if (!$info||$info[0]*$info[1]>20000000) throw new Problem(422,'Ảnh không hợp lệ hoặc quá lớn.'); }
        $key=bin2hex(random_bytes(32));$dir=WRITEPATH.'uploads';if (!is_dir($dir)) mkdir($dir,0700,true);file_put_contents($dir.'/'.$key,$data);
        try { $out=$this->s->insert('files',['employee_id'=>$employee['id'],'name'=>mb_substr(basename($v['name']??'document'),0,200),'storage_key'=>$key,'mime'=>$mime,'size'=>strlen($data)]);unset($out['storage_key']);return $out; } catch(\Throwable $e) { unlink($dir.'/'.$key);throw $e; }
    }
    public function download(int $id,$response)
    {
        (new Resources($this->s))->authorize('files');$file=$this->s->find('files',$id);
        if (!preg_match('/^[a-f0-9]{64}$/',$file['storage_key'])) throw new Problem(404,'Không tìm thấy tệp.');
        $path=WRITEPATH.'uploads/'.$file['storage_key'];if (!is_file($path)) throw new Problem(404,'Không tìm thấy tệp.');
        $this->s->audit('download','files',$id,null,null);
        return $response->setContentType($file['mime'])->setHeader('Cache-Control','no-store')->setHeader('Content-Disposition','attachment; filename="document-'.(int)$id.'"')->setBody(file_get_contents($path));
    }
}
