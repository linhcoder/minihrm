<?php
use App\Domain\{Context,GuidancePreferences,Problem};
use CodeIgniter\Test\CIUnitTestCase;
final class GuidancePreferencesTest extends CIUnitTestCase
{
    protected function setUp():void{
        parent::setUp();$config=config('Database')->tests;$config['DBPrefix']='';$this->db=\Config\Database::connect($config,false);$forge=\Config\Database::forge($this->db);
        $forge->addField(['id'=>['type'=>'INT','unsigned'=>true]]);$forge->addKey('id',true);$forge->createTable('users');$this->db->table('users')->insertBatch([['id'=>1],['id'=>2]]);
        require_once APPPATH.'Database/Migrations/2026-10-27-000005_GuidancePreferences.php';(new \App\Database\Migrations\GuidancePreferences($forge))->up();
    }
    protected function tearDown():void{$this->db->close();parent::tearDown();}
    private function preferences(int $user=1,int $tenant=10,string $role='owner',bool $super=false):GuidancePreferences{return new GuidancePreferences($this->db,new Context(['id'=>$user,'is_super'=>$super],['role'=>$role],['id'=>$tenant]));}
    public function testFirstVisitAndPersistentCompletion():void{
        $p=$this->preferences();$this->assertSame('new',$p->current()['status']);$p->save(['version'=>1,'status'=>'completed']);$this->assertSame('completed',$this->preferences()->current()['status']);
        $p->save(['version'=>1,'status'=>'dismissed']);$this->assertSame(1,$this->db->table('guidance_preferences')->countAllResults());
    }
    public function testClientIdsCannotWriteAnotherAccountOrTenantPreference():void{
        $this->preferences()->save(['version'=>1,'status'=>'completed','user_id'=>2,'tenant_id'=>20]);
        $this->assertSame('new',$this->preferences(2)->current()['status']);$this->assertSame('new',$this->preferences(1,20)->current()['status']);$this->assertSame('completed',$this->preferences()->current()['status']);
    }
    public function testRoleAndPlatformContextsRemainSeparate():void{
        $this->preferences()->save(['version'=>1,'status'=>'dismissed']);$this->assertSame('new',$this->preferences(1,10,'employee')->current()['status']);
        $this->assertSame('new',$this->preferences(1,10,'owner',true)->current()['status']);
    }
    public function testInvalidStatusCannotSuppressFirstTimeGuidance():void{$this->expectException(Problem::class);$this->preferences()->save(['version'=>1,'status'=>'unknown']);}
    public function testUnsupportedVersionIsRejected():void{$this->expectException(Problem::class);$this->preferences()->save(['version'=>999,'status'=>'completed']);}
}
