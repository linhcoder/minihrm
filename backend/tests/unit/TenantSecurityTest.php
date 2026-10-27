<?php
use CodeIgniter\Test\CIUnitTestCase;
use App\Domain\{Auth,Context,Store,Resources,Problem,Schema,Subscriptions,Workflow,PayrollEngine};
final class TenantSecurityTest extends CIUnitTestCase
{
    private Store $a;private Store $b;
    protected function setUp():void
    {
        parent::setUp();$cfg=config('Database')->tests;$cfg['DBPrefix']='';$this->db=\Config\Database::connect($cfg,false);$forge=\Config\Database::forge($this->db);
        require_once APPPATH.'Database/Migrations/2026-10-27-000001_CreatePlatform.php';require_once APPPATH.'Database/Migrations/2026-10-27-000002_AddIntegrity.php';
        (new \App\Database\Migrations\CreatePlatform($forge))->up();(new \App\Database\Migrations\AddIntegrity($forge))->up();
        $this->db->table('plans')->insert(['name'=>'Test','price'=>199000,'employee_limit'=>10,'features'=>'["attendance","timesheets","payroll","export","leave","branches","gps","advanced_scheduling","advanced_reports"]']);
        foreach(['A','B'] as $name){$this->db->table('tenants')->insert(['name'=>$name,'timezone'=>'Asia/Ho_Chi_Minh','status'=>'active','working_days'=>'[1,2,3,4,5,6]']);$tid=(int)$this->db->insertID();
            $this->db->table('users')->insert(['email'=>strtolower($name).'@test.local','full_name'=>$name,'password_hash'=>password_hash('A-StrongPassword123',PASSWORD_DEFAULT),'active'=>1,'verified_at'=>Store::now()]);$uid=(int)$this->db->insertID();
            $m=['tenant_id'=>$tid,'user_id'=>$uid,'role'=>'owner','permissions'=>'[]','branch_ids'=>'[]'];$this->db->table('tenant_memberships')->insert($m);
            $u=$this->db->table('users')->where('id',$uid)->get()->getRowArray();$t=$this->db->table('tenants')->where('id',$tid)->get()->getRowArray();$s=new Store($this->db,new Context($u,$m,$t));
            $s->insert('subscriptions',['plan_id'=>1,'status'=>'active','starts_at'=>'2026-01-01']);$s->insert('branches',['name'=>$name,'timezone'=>'Asia/Ho_Chi_Minh','attendance_policy'=>'basic']);
            $s->insert('employees',['code'=>'NV1','full_name'=>$name.' Employee','email'=>$name.'emp@test.local','branch_id'=>$tid,'hire_date'=>'2026-01-01','status'=>'active','base_salary'=>8000000,'salary_type'=>'monthly']);
            if($name==='A')$this->a=$s;else $this->b=$s;
        }
    }
    protected function tearDown():void{$this->db->close();parent::tearDown();}
    private function denied(callable $fn,int $status):void{try{$fn();$this->fail('Access should fail');}catch(Problem $e){$this->assertSame($status,$e->status);}}
    public static function resources():array{return array_map(fn($r)=>[$r],['employees','attendance','timesheets','payroll-periods','payslips','files','branches','leave-requests']);}
    /** @dataProvider resources */
    public function testCrossTenantObjectsAreInvisible(string $r):void
    {
        if($r==='employees'||$r==='branches')$row=$this->b->query($r)->get()->getRowArray();else{
            $v=[];foreach(Schema::RESOURCES[$r] as $k=>$t){if(str_ends_with($t,'!'))$v[$k]=str_starts_with($t,'ref:')?2:(str_starts_with($t,'money')?100:'test');}
            if($r==='attendance')$v['idempotency_key']='security-test-unique-123';$row=$this->b->insert($r,$v);
        }
        $this->denied(fn()=>$this->a->find($r,(int)$row['id']),404);
    }
    public function testClientTenantIdCannotSwitchOwnership():void
    {
        $r=(new Resources($this->a))->save('employees',['tenant_id'=>2,'code'=>'NEW','full_name'=>'New','email'=>'new@test.local','hire_date'=>'2026-01-01','branch_id'=>1]);$this->assertSame(1,(int)$r['tenant_id']);
    }
    public function testCrossTenantForeignKeyIsRejected():void
    {
        $this->denied(fn()=>(new Resources($this->a))->save('employees',['code'=>'NEW','full_name'=>'New','email'=>'new@test.local','hire_date'=>'2026-01-01','branch_id'=>2]),404);
    }
    public function testEmployeeSeesOnlyOwnPublishedPayslips():void
    {
        $ctx=new Context($this->a->ctx->user,[...$this->a->ctx->membership,'role'=>'employee','employee_id'=>1],$this->a->ctx->tenant);$s=new Store($this->db,$ctx);
        foreach([[1,'draft'],[1,'published'],[99,'published']] as $i=>[$e,$status])$this->a->insert('payslips',['employee_id'=>$e,'payroll_period_id'=>$i+1,'status'=>$status]);
        $rows=(new Resources($s))->list('payslips',[])['data'];$this->assertCount(1,$rows);$this->assertSame('published',$rows[0]['status']);
    }
    public function testManagerCannotReadPayrollOrOtherBranches():void
    {
        $ctx=new Context($this->a->ctx->user,[...$this->a->ctx->membership,'role'=>'manager','branch_ids'=>'[]'],$this->a->ctx->tenant);$s=new Store($this->db,$ctx);
        $this->denied(fn()=>(new Resources($s))->list('payslips',[]),403);$this->assertSame(0,$s->query('employees')->countAllResults());
    }
    public function testManagerCanReadScheduleLookupsButCannotEditPeople():void
    {
        $ctx=new Context($this->a->ctx->user,[...$this->a->ctx->membership,'role'=>'manager','branch_ids'=>'[1]'],$this->a->ctx->tenant);$s=new Store($this->db,$ctx);
        $rows=(new Resources($s))->list('employees',[])['data'];$this->assertCount(1,$rows);$this->assertArrayNotHasKey('base_salary',$rows[0]);
        $this->denied(fn()=>(new Resources($s))->save('employees',['full_name'=>'Unauthorized'],1),403);
        $this->denied(fn()=>(new Resources($s))->archive('employees',1),403);
    }
    public function testExpiredPlanPreservesReadsButBlocksWrites():void
    {
        $this->a->update('subscriptions',1,['ends_at'=>'2020-01-01']);$this->assertCount(1,(new Resources($this->a))->list('employees',[])['data']);
        $this->denied(fn()=>(new Resources($this->a))->save('departments',['name'=>'Blocked']),402);
    }
    public function testEmployeeLimitCannotBeBypassed():void
    {
        $this->a->update('subscriptions',1,['employee_limit'=>1]);$this->denied(fn()=>(new Resources($this->a))->save('employees',['code'=>'NEW','full_name'=>'New','email'=>'new@test.local','hire_date'=>'2026-01-01']),402);
    }
    public function testRefreshIsSingleUseAndLogoutRevokesAccess():void
    {
        $auth=new Auth($this->db);$l=$auth->login(['email'=>'A@test.local','password'=>'A-StrongPassword123'],'127.0.0.1');$r=$auth->refresh($l['refresh_token']);
        $this->denied(fn()=>$auth->refresh($l['refresh_token']),401);$this->denied(fn()=>$auth->context($l['access_token']),401);$auth->logout($r['access_token']);$this->denied(fn()=>$auth->context($r['access_token']),401);
    }
    public function testLockedPayrollCannotBeRecalculatedOrAdjusted():void
    {
        $p=$this->a->insert('salary-policies',['name'=>'Policy','effective_from'=>'2026-01-01','standard_days'=>26,'standard_minutes'=>480,'version'=>1]);
        $period=$this->a->insert('payroll-periods',['name'=>'October','start_date'=>'2026-10-01','end_date'=>'2026-10-31','policy_id'=>$p['id'],'status'=>'locked']);
        $this->denied(fn()=>(new PayrollEngine($this->a))->run((int)$period['id']),409);
        $this->denied(fn()=>(new Resources($this->a))->save('payroll-adjustments',['employee_id'=>1,'payroll_period_id'=>$period['id'],'kind'=>'bonus','amount'=>10000,'reason'=>'Test']),409);
    }
    public function testArchivedEmployeesPreserveHistory():void
    {
        $result=(new Resources($this->a))->archive('employees',1);$this->assertTrue($result['archived']);$this->denied(fn()=>$this->a->find('employees',1),404);$this->assertSame(1,$this->db->table('employees')->where('id',1)->countAllResults());
    }
    public function testInactiveEmployeeCannotUseCompanyContextWithoutDisablingGlobalUser():void
    {
        $this->db->table('tenant_memberships')->where('tenant_id',1)->update(['employee_id'=>1,'role'=>'employee']);
        $this->db->table('employees')->where('tenant_id',1)->where('id',1)->update(['status'=>'inactive']);
        $auth=new Auth($this->db);$tokens=$auth->login(['email'=>'a@test.local','password'=>'A-StrongPassword123'],'127.0.0.1');
        $this->denied(fn()=>$auth->context($tokens['access_token']),403);
        $this->assertSame(1,(int)$this->db->table('users')->where('id',1)->get()->getRowArray()['active']);
        $other=$auth->login(['email'=>'b@test.local','password'=>'A-StrongPassword123'],'127.0.0.1');$this->assertSame(2,$auth->context($other['access_token'])->tenantId());
    }
}
