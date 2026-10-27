<?php
namespace App\Domain;
use CodeIgniter\Database\BaseConnection;
/** User-owned UI preferences. Scope is always derived from authorized context, never request IDs. */
final class GuidancePreferences
{
    public const VERSION=1;
    public function __construct(private BaseConnection $db,private Context $ctx){}
    private function scope():string{return !empty($this->ctx->user['is_super'])?'platform':'tenant:'.$this->ctx->tenantId().':role:'.$this->ctx->membership['role'];}
    public function current():array{
        $row=$this->db->table('guidance_preferences')->select('version,status,updated_at')->where('user_id',$this->ctx->user['id'])->where('scope',$this->scope())->get()->getRowArray();
        return $row?['version'=>(int)$row['version'],'status'=>$row['status'],'updated_at'=>$row['updated_at']]:['version'=>0,'status'=>'new','updated_at'=>null];
    }
    public function save(array $input):array{
        if(($input['version']??null)!==self::VERSION||!in_array($input['status']??'', ['dismissed','completed'],true))throw new Problem(422,'Trạng thái hướng dẫn không hợp lệ.');
        $this->db->table('guidance_preferences')->upsert(['user_id'=>$this->ctx->user['id'],'scope'=>$this->scope(),'version'=>self::VERSION,'status'=>$input['status'],'updated_at'=>Store::now()]);
        return $this->current();
    }
}
