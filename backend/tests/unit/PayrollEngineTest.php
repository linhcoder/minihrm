<?php
use App\Domain\{PayrollEngine,AttendanceEngine,Problem};
use CodeIgniter\Test\CIUnitTestCase;
final class PayrollEngineTest extends CIUnitTestCase
{
    private function policy():array{return ['standard_days'=>26,'standard_minutes'=>480,'overtime_multiplier_bps'=>15000,'version'=>3];}
    public function testIllustrativeBreakdownUsesIntegerVnd():void
    {
        $out=PayrollEngine::calculate(['base_salary'=>8000000,'allowance'=>500000,'salary_type'=>'monthly'],[['worked_minutes'=>12480,'overtime_minutes'=>0,'review_status'=>'approved']],$this->policy(),[['kind'=>'bonus','amount'=>750000,'status'=>'approved'],['kind'=>'deduction','amount'=>200000,'status'=>'approved']],1000000);
        $this->assertSame(8050000,$out['net']);$this->assertSame('non_statutory_estimate',$out['breakdown']['compliance']);$this->assertIsInt($out['net']);
    }
    public function testHourlyAndOvertimeHaveNoFloatingPoint():void
    {
        $out=PayrollEngine::calculate(['base_salary'=>30000,'allowance'=>0,'salary_type'=>'hourly'],[['worked_minutes'=>600,'overtime_minutes'=>120,'review_status'=>'approved']],$this->policy());
        $this->assertSame(330000,$out['net']);$this->assertSame(90000,$out['breakdown']['overtime']);
    }
    public function testPendingTimesheetsNeverBecomePayrollInput():void
    {
        $this->expectException(Problem::class);PayrollEngine::calculate(['base_salary'=>8000000,'salary_type'=>'monthly'],[['worked_minutes'=>480,'overtime_minutes'=>0,'review_status'=>'pending']],$this->policy());
    }
    public function testZeroDenominatorIsRejected():void{$this->expectException(Problem::class);PayrollEngine::divide(100,0);}
    public function testHalfUpRounding():void{$this->assertSame(3,PayrollEngine::divide(5,2));$this->assertSame(1,PayrollEngine::divide(4,3));}
    public function testUnpaidAbsenceReducesMonthlyBase():void
    {
        $r=PayrollEngine::calculate(['base_salary'=>7800000,'allowance'=>0,'salary_type'=>'monthly'],[['worked_minutes'=>12000,'overtime_minutes'=>0,'review_status'=>'approved']],$this->policy());$this->assertSame(7500000,$r['net']);
    }
    public function testPaidLeaveContributesMinutes():void
    {
        $r=PayrollEngine::calculate(['base_salary'=>7800000,'allowance'=>0,'salary_type'=>'monthly'],[['worked_minutes'=>12000,'overtime_minutes'=>0,'review_status'=>'approved']],$this->policy(),[],0,480);$this->assertSame(7800000,$r['net']);
    }
    public function testOvernightShiftAndUnpaidBreak():void
    {
        $shift=['start_time'=>'22:00','end_time'=>'06:00','break_minutes'=>30,'paid_break'=>0,'grace_minutes'=>5,'overtime_eligible'=>1];
        $r=AttendanceEngine::normalize([['kind'=>'check_in','occurred_at'=>'2026-10-26T15:10:00Z','status'=>'verified'],['kind'=>'check_out','occurred_at'=>'2026-10-26T23:00:00Z','status'=>'verified']],['work_date'=>'2026-10-26'],$shift,'Asia/Ho_Chi_Minh');
        $this->assertSame(440,$r['worked_minutes']);$this->assertSame(5,$r['late_minutes']);$this->assertSame('late',$r['status']);
    }
    public function testOfflineAttemptCannotCountAsVerifiedTime():void
    {
        $r=AttendanceEngine::normalize([['kind'=>'check_in','occurred_at'=>'2026-10-26T01:00:00Z','status'=>'pending_verification']],['work_date'=>'2026-10-26'],['start_time'=>'08:00','end_time'=>'17:00','break_minutes'=>60,'paid_break'=>0,'grace_minutes'=>0,'overtime_eligible'=>1],'Asia/Ho_Chi_Minh');
        $this->assertSame(0,$r['worked_minutes']);$this->assertSame('pending_verification',$r['status']);
    }
    public function testDstShiftUsesElapsedUtcTime():void
    {
        [$a,$b]=AttendanceEngine::window(['work_date'=>'2026-11-01'],['start_time'=>'00:00','end_time'=>'08:00'],'America/New_York');$this->assertSame(540,intdiv($b->getTimestamp()-$a->getTimestamp(),60));
    }
    public function testMultipleEventsAndMissingCheckout():void
    {
        $r=AttendanceEngine::normalize([['kind'=>'check_in','occurred_at'=>'2026-10-26T01:00:00Z','status'=>'verified'],['kind'=>'check_out','occurred_at'=>'2026-10-26T05:00:00Z','status'=>'verified'],['kind'=>'check_in','occurred_at'=>'2026-10-26T06:00:00Z','status'=>'verified']],['work_date'=>'2026-10-26'],['start_time'=>'08:00','end_time'=>'17:00','break_minutes'=>60,'paid_break'=>0,'grace_minutes'=>0,'overtime_eligible'=>1],'Asia/Ho_Chi_Minh');
        $this->assertSame('missing_check_out',$r['status']);$this->assertNull($r['check_out']);
    }
    public function testGpsDistance():void{$this->assertEqualsWithDelta(0,AttendanceEngine::distance(10,106,10,106),0.01);$this->assertGreaterThan(1000,AttendanceEngine::distance(10,106,10.02,106));}
    public function testOngoingShiftIsPresentUntilCheckoutDeadline():void
    {
        $r=AttendanceEngine::normalize([['kind'=>'check_in','occurred_at'=>'2026-10-27T01:00:00Z','status'=>'verified']],['work_date'=>'2026-10-27'],['start_time'=>'08:00','end_time'=>'17:00','break_minutes'=>60,'paid_break'=>0,'grace_minutes'=>0,'overtime_eligible'=>1],'Asia/Ho_Chi_Minh',new \DateTimeImmutable('2026-10-27T03:00:00Z'));
        $this->assertSame('present',$r['status']);$this->assertNull($r['check_out']);$this->assertSame('pending',$r['review_status']);
    }
}
