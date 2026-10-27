<?php
namespace App\Controllers;
use App\Domain\{Auth,Context,Store,Schema,Problem,Resources,Subscriptions,Onboarding,Dashboard,AttendanceEngine,Workflow};
final class Api extends BaseController
{
    public function dispatch(string $path='')
    {
        try {
            $method=strtolower($this->request->getMethod());
            $v=in_array($method,['post','put','patch'])?($this->request->getJSON(true)??[]):[];
            if (!is_array($v)) throw new Problem(422,'JSON phải là một đối tượng.');
            $db=db_connect();$auth=new Auth($db);$path=trim($path,'/');
            $bearer=preg_replace('/^Bearer\s+/i','',$this->request->getHeaderLine('Authorization'));
            $out=match($method.' '.$path) {
                'post auth/login'=>$auth->login($v,$this->request->getIPAddress()),'post auth/register'=>$auth->register($v),
                'post auth/refresh'=>$auth->refresh($v['refresh_token']??''),'post auth/forgot'=>$auth->forgot($v['email']??''),
                'post auth/verify'=>$auth->redeem('verify',$v),'post auth/reset'=>$auth->redeem('reset',$v),default=>null,
            };
            if ($out!==null) return $this->json($out,$path==='auth/register'?201:200);
            if ($path==='health'&&$method==='get') { $db->query('SELECT 1');return $this->json(['status'=>'ok']); }
            if ($path==='billing/webhook'&&$method==='post') return (new \App\Domain\Billing($db))->webhook($this->request->getBody(),$this->request->getHeaderLine('X-Signature'))?$this->json(['accepted'=>true]):$this->json(['accepted'=>false],400);
            $ctx=$auth->context($bearer);
            if ($path==='auth/me'&&$method==='get') return $this->json(['user'=>$ctx->user,'tenant'=>$ctx->tenant,'membership'=>$ctx->membership]);
            if ($path==='auth/logout'&&$method==='post') { $auth->logout($bearer);return $this->json(['logged_out'=>true]); }
            if ($path==='onboarding'&&$method==='post') return $this->json((new Onboarding($db))->create($ctx,$v),201);
            if (str_starts_with($path,'super/')) {
                if (!$ctx->user['is_super']) throw new Problem(403,'Chỉ dành cho quản trị hệ thống.');
                return $this->json((new \App\Domain\Platform($db,$ctx))->handle(substr($path,6),$method,$v,$this->request->getGet()));
            }
            if ($ctx->user['is_super']) throw new Problem(403,'Quản trị hệ thống không có quyền truy cập nghiệp vụ công ty.');
            $maintenance=$db->table('settings')->where('name','maintenance')->get()->getRowArray();
            if($maintenance&&json_decode($maintenance['value'],true)===true)throw new Problem(503,'Hệ thống đang bảo trì. Vui lòng quay lại sau.');
            $ctx->tenantId();$s=new Store($db,$ctx);$resources=new Resources($s);$segments=explode('/',$path);$r=$segments[0];$id=isset($segments[1])&&ctype_digit($segments[1])?(int)$segments[1]:null;$action=$segments[2]??null;
            if ($path==='schema'&&$method==='get') return $this->json(['resources'=>Schema::RESOURCES]);
            if ($path==='dashboard'&&$method==='get') return $this->json((new Dashboard($s))->company());
            if ($path==='mobile-home'&&$method==='get') return $this->json($this->mobileHome($s));
            if($path==='push-device'&&$method==='post'){
                $token=$v['token']??'';if(!is_string($token)||strlen($token)<20||strlen($token)>512)throw new Problem(422,'Push token không hợp lệ.');
                $hash=hash('sha256',$token);$old=$db->table('push_devices')->where('token_hash',$hash)->get()->getRowArray();
                if($old){if((int)$old['user_id']!==(int)$ctx->user['id']||(int)$old['tenant_id']!==$ctx->tenantId())throw new Problem(409,'Thiết bị đã liên kết tài khoản khác.');}
                else $db->table('push_devices')->insert(['tenant_id'=>$ctx->tenantId(),'user_id'=>$ctx->user['id'],'token'=>$token,'token_hash'=>$hash,'created_at'=>Store::now()]);
                return $this->json(['registered'=>true]);
            }
            if ($path==='subscriptions'&&$method==='get') return $this->json((new Subscriptions($s))->current());
            if ($path==='plans'&&$method==='get') return $this->json(['data'=>$db->table('plans')->get()->getResultArray()]);
            if ($r==='billing') return $this->json((new \App\Domain\Billing($db,$s))->company($segments[1]??'',$method,$v));
            if ($path==='audit'&&$method==='get') { $ctx->require('settings');return $this->json(['data'=>$s->query('audit-logs')->orderBy('id','DESC')->limit(200)->get()->getResultArray()]); }
            if ($path==='settings') return $this->json($this->settings($s,$method,$v));
            if ($path==='memberships') return $this->json($this->memberships($s,$method,$v));
            if ($path==='schedules/bulk'&&$method==='post') return $this->json($this->bulk($s,$v),201);
            if ($path==='timesheets/aggregate'&&$method==='post') return $this->json($this->aggregate($s,$v));
            if ($path==='attendance/attempt'&&$method==='post') return $this->json((new AttendanceEngine($s))->attempt($v),201);
            if ($r==='reports') return $this->json((new \App\Domain\Reports($s))->run($segments[1]??'daily-attendance',$this->request->getGet()));
            if ($r==='exports') return (new \App\Domain\Exports($s))->download($segments[1]??'employees',$this->request->getGet(),$this->response);
            if ($r==='imports'&&$method==='post') return $this->json((new \App\Domain\Exports($s))->import($v),201);
            if($path==='imports/template'&&$method==='get')return (new \App\Domain\Exports($s))->template($this->response);
            if ($r==='files'&&$method==='post'&&($segments[1]??'')==='upload') return $this->json((new \App\Domain\Files($s))->upload($v),201);
            if ($r==='files'&&$id&&$action==='download'&&$method==='get') return (new \App\Domain\Files($s))->download($id,$this->response);
            if ($r==='privacy-requests'&&$id&&$action==='download'&&$method==='get') return (new \App\Domain\Privacy($s))->download($id,$this->response);
            if($r==='employees'&&$id&&$action==='history'&&$method==='get'){
                $ctx->require('people');$s->find('employees',$id);
                $rows=$s->query('audit-logs')->where('resource','employees')->where('record_id',$id)->orderBy('id','DESC')->get()->getResultArray();
                foreach($rows as &$row)foreach(['before_value','after_value'] as $k){$decoded=json_decode($row[$k]??'null',true);if(is_array($decoded))$row[$k]=$resources->redact('employees',$decoded);}unset($row);
                return $this->json(['data'=>$rows]);
            }
            $resources->authorize($r);
            if ($method==='get') return $this->json($id?['data'=>$resources->redact($r,$s->find($r,$id))]:$resources->list($r,$this->request->getGet()));
            if ($method==='post'&&$id&&$action) {$out=(new Workflow($s))->act($r,$id,$action,$v);return $this->json($r==='employees'?$resources->redact($r,$out):$out);}
            if ($method==='post'&&!$id) return $this->json($resources->save($r,$v),201);
            if (in_array($method,['patch','put'])&&$id) return $this->json($resources->save($r,$v,$id));
            if ($method==='delete'&&$id) return $this->json($resources->archive($r,$id));
            throw new Problem(405,'Phương thức không được hỗ trợ.');
        } catch (Problem $e) { return $this->json(['error'=>['code'=>$e->status,'message'=>$e->getMessage(),'details'=>$e->details]],$e->status); }
        catch (\CodeIgniter\HTTP\Exceptions\HTTPException|\JsonException $e) { return $this->json(['error'=>['code'=>400,'message'=>'JSON không hợp lệ.']],400); }
        catch (\Throwable $e) { log_message('error','{message}',['message'=>(string)$e]);return $this->json(['error'=>['code'=>500,'message'=>'Lỗi xử lý. Vui lòng thử lại hoặc liên hệ quản trị.']],500); }
    }
    private function json(array $v,int $status=200) { return $this->response->setStatusCode($status)->setHeader('Cache-Control','no-store')->setHeader('X-Content-Type-Options','nosniff')->setJSON($v); }
    private function settings(Store $s,string $method,array $v): array
    {
        $s->ctx->require('settings');if ($method==='get') return ['data'=>$s->ctx->tenant];
        (new Subscriptions($s))->writable();$values=array_intersect_key($v,array_flip(['name','retention_days','payroll_day','working_days']));
        if (isset($values['retention_days'])) $values['retention_days']=max(30,min(3650,(int)$values['retention_days']));
        if (isset($values['payroll_day'])) $values['payroll_day']=max(1,min(28,(int)$values['payroll_day']));
        if (isset($values['working_days'])) { if (!is_array($values['working_days'])||!$values['working_days']||array_diff($values['working_days'],[1,2,3,4,5,6,7])) throw new Problem(422,'Ngày làm việc không hợp lệ.');$values['working_days']=json_encode($values['working_days']); }
        $s->db->table('tenants')->where('id',$s->ctx->tenantId())->update($values);$s->audit('settings','tenants',$s->ctx->tenantId(),$s->ctx->tenant,$values);return ['saved'=>true];
    }
    private function mobileHome(Store $s): array
    {
        $employee=(int)($s->ctx->membership['employee_id']??0);if (!$employee) throw new Problem(403,'Tài khoản chưa liên kết hồ sơ nhân viên.');
        $emp=$s->find('employees',$employee);if ($emp['status']!=='active') throw new Problem(403,'Nhân viên không hoạt động.');
        $today=(new \DateTimeImmutable('now',new \DateTimeZone($s->ctx->tenant['timezone'])))->format('Y-m-d');
        $yesterday=(new \DateTimeImmutable($today))->modify('-1 day')->format('Y-m-d');
        $rows=$s->query('schedules')->where('employee_id',$employee)->where('work_date >=',$yesterday)->where('work_date <=',$today)->where('status !=','cancelled')->orderBy('work_date')->get()->getResultArray();$out=[];
        foreach($rows as $row) {
            $shift=$s->find('shifts',(int)$row['shift_id']);$branch=$s->db->table('branches')->where('tenant_id',$s->ctx->tenantId())->where('id',$row['branch_id'])->get()->getRowArray();
            [$start,$end]=AttendanceEngine::window($row,$shift,$branch['timezone']?:$s->ctx->tenant['timezone']);
            if($row['work_date']<$today&&$end->modify('+'.(int)$shift['latest_minutes'].' minutes')<new \DateTimeImmutable('now',new \DateTimeZone('UTC'))) continue;
            $last=$s->query('attendance')->where('schedule_id',$row['id'])->orderBy('id','DESC')->get()->getRowArray();
            $out[]=[...$row,'shift_name'=>$shift['name'],'start_time'=>$shift['start_time'],'end_time'=>$shift['end_time'],'last_kind'=>$last['kind']??null,'branch'=>array_intersect_key($branch,array_flip(['name','attendance_policy','radius','latitude','longitude']))];
        }
        return ['employee'=>(new Resources($s))->redact('employees',$emp),'schedules'=>$out,'recent'=>$s->query('attendance')->where('employee_id',$employee)->orderBy('id','DESC')->limit(10)->get()->getResultArray(),'leave_types'=>$s->query('leave-types')->get()->getResultArray()];
    }
    private function memberships(Store $s,string $method,array $v): array
    {
        $s->ctx->require('settings');if (($s->ctx->membership['role']??'')!=='owner') throw new Problem(403,'Chỉ chủ công ty được phân quyền.');
        if ($method==='get') return ['data'=>$s->query('tenant-memberships')->get()->getResultArray()];
        (new Subscriptions($s))->writable();$m=$s->find('tenant-memberships',(int)($v['id']??0));if ($m['role']==='owner') throw new Problem(403,'Không thể thay quyền chủ công ty.');
        if (!in_array($v['role']??'', ['admin','manager','employee'])) throw new Problem(422,'Vai trò không hợp lệ.');
        $permissions=$v['permissions']??[];if (!is_array($permissions)||array_diff($permissions,['people','schedule','attendance','leave','reports','settings','invite','payroll'])) throw new Problem(422,'Quyền không hợp lệ.');
        foreach ($v['branch_ids']??[] as $id) $s->find('branches',(int)$id);
        return $s->update('tenant-memberships',(int)$m['id'],['role'=>$v['role'],'permissions'=>json_encode($permissions),'branch_ids'=>json_encode($v['branch_ids']??[])]);
    }
    private function bulk(Store $s,array $v): array
    {
        $s->ctx->require('schedule');(new Subscriptions($s))->writable('advanced_scheduling');
        return $s->transaction(function() use($s,$v) { $s->lockTenant();$rows=$v['rows']??[];
            if (isset($v['copy_from'])) { $start=$v['copy_from'];$end=(new \DateTimeImmutable($start))->modify('+6 days')->format('Y-m-d');$source=$s->query('schedules')->where('work_date >=',$start)->where('work_date <=',$end)->where('status !=','cancelled')->get()->getResultArray();foreach ($source as $row) $rows[]=['employee_id'=>$row['employee_id'],'branch_id'=>$row['branch_id'],'shift_id'=>$row['shift_id'],'work_date'=>(new \DateTimeImmutable($row['work_date']))->modify('+7 days')->format('Y-m-d')]; }
            if (count($rows)>500) throw new Problem(422,'Tối đa 500 ca mỗi lần.');$out=[];foreach($rows as $row) $out[]=(new Resources($s))->save('schedules',$row);return ['data'=>$out]; });
    }
    private function aggregate(Store $s,array $v): array
    {
        $s->ctx->require('attendance');(new Subscriptions($s))->writable();
        return $s->transaction(function() use($s,$v) { $s->lockTenant();$from=$v['from']??gmdate('Y-m-d');$to=$v['to']??$from;
            if ($from>$to||(new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($to))->days>62) throw new Problem(422,'Tổng hợp tối đa 62 ngày.');
            $rows=$s->query('schedules')->where('work_date >=',$from)->where('work_date <=',$to)->where('status !=','cancelled')->get()->getResultArray();$n=0;
            foreach($rows as $row) { $old=$s->query('timesheets')->where('schedule_id',$row['id'])->get()->getRowArray();if ($old&&$old['review_status']==='approved') continue;$sheet=(new AttendanceEngine($s))->aggregate($row);
                $leave=$s->query('leave-requests')->where('employee_id',$row['employee_id'])->where('status','approved')->where('start_date <=',$row['work_date'])->where('end_date >=',$row['work_date'])->get()->getRowArray();
                if ($leave) $s->update('timesheets',(int)$sheet['id'],['status'=>'on_leave']);elseif ($s->query('holidays')->where('work_date',$row['work_date'])->countAllResults()) $s->update('timesheets',(int)$sheet['id'],['status'=>'holiday']);$n++;
            }return ['aggregated'=>$n]; });
    }
}
