<?php
namespace App\Domain;
final class Privacy
{
    public function __construct(private Store $s) {}
    public function fulfill(array $request): array
    {
        if(!in_array($request['kind'],['access','export','anonymize']))throw new Problem(422,'Loại yêu cầu dữ liệu không hợp lệ.');
        $membership=$this->s->db->table('tenant_memberships')->where('tenant_id',$this->s->ctx->tenantId())->where('user_id',$request['user_id'])->get()->getRowArray();
        if(!$membership)throw new Problem(404,'Không tìm thấy thành viên.');
        $employee=(int)$membership['employee_id'];
        if($request['kind']==='anonymize') {
            if($membership['role']==='owner')throw new Problem(409,'Cần chuyển quyền sở hữu trước khi ẩn danh tài khoản chủ công ty.');
            // Keep IDs, time and financial records for accounting; remove direct contact identifiers.
            if($employee)$this->s->update('employees',$employee,['full_name'=>'Nhân viên đã ẩn danh #'.$employee,'email'=>'anonymized-'.$employee.'@invalid.local','phone'=>null,'birth_date'=>null,'emergency_contact'=>null,'notes'=>null,'status'=>'inactive']);
            $this->s->db->table('users')->where('id',$request['user_id'])->update(['full_name'=>'Đã ẩn danh','email'=>'anonymized-user-'.$request['user_id'].'@invalid.local','active'=>0,'password_hash'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT)]);
            $this->s->db->table('auth_tokens')->where('user_id',$request['user_id'])->update(['revoked'=>1]);
            $this->s->db->table('attendance')->where('tenant_id',$this->s->ctx->tenantId())->where('employee_id',$employee)->update(['latitude'=>null,'longitude'=>null,'device_id'=>null]);
            return ['anonymized'=>true,'retained'=>'Attendance, payroll snapshots and audit evidence retained for legitimate accounting.'];
        }
        $data=['generated_at'=>Store::now(),'notice'=>'Personal data export for the authenticated data subject.'];
        foreach(['employees','attendance','timesheets','leave-requests','salary-advances','payslips'] as $r) {
            $q=$this->s->db->table(Schema::table($r))->where('tenant_id',$this->s->ctx->tenantId())->where($r==='employees'?'id':'employee_id',$employee?:-1);
            if($r==='payslips')$q->where('status','published');
            $data[$r]=$q->get()->getResultArray();foreach($data[$r] as &$row)unset($row['emergency_contact'],$row['inputs'],$row['device_id']);unset($row);
        }
        $dir=WRITEPATH.'privacy';if(!is_dir($dir))mkdir($dir,0700,true);file_put_contents($dir.'/'.(int)$request['id'].'.json',json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        return ['prepared'=>true,'download'=>'/api/v1/privacy-requests/'.$request['id'].'/download'];
    }
    public function download(int $id,$response)
    {
        $r=$this->s->find('privacy-requests',$id);
        if((int)$r['user_id']!==(int)$this->s->ctx->user['id'])throw new Problem(403,'Chỉ chủ dữ liệu được tải bản xuất cá nhân.');
        if($r['status']!=='approved'||!in_array($r['kind'],['access','export']))throw new Problem(409,'Yêu cầu chưa được duyệt.');
        $path=WRITEPATH.'privacy/'.$id.'.json';if(!is_file($path))throw new Problem(404,'Bản xuất không còn khả dụng. Gửi yêu cầu mới.');
        if(filemtime($path)<time()-7*86400)throw new Problem(410,'Bản xuất đã hết hạn sau 7 ngày. Gửi yêu cầu mới.');
        $this->s->audit('privacy_download','privacy-requests',$id,null,null);
        return $response->setContentType('application/json')->setHeader('Content-Disposition','attachment; filename="my-hrm-data.json"')->setHeader('Cache-Control','no-store')->setBody(file_get_contents($path));
    }
}
