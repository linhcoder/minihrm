<?php
use App\Domain\{DateFormat,Problem};
use CodeIgniter\Test\CIUnitTestCase;
final class DateFormatTest extends CIUnitTestCase
{
    public function testDateAndTimestampDisplayUseVietnamTime():void{
        $this->assertSame('27/10/2026',DateFormat::display('2026-10-27'));
        $this->assertSame('28/10/2026 01:05:09',DateFormat::display('2026-10-27T18:05:09Z'));
        $this->assertSame('08:00:00',DateFormat::display('08:00'));
    }
    public function testVietnameseInputBecomesCanonicalStorage():void{
        $this->assertSame('2024-02-29',DateFormat::date('29/02/2024'));
        $this->assertSame('2026-10-27T01:00:00Z',DateFormat::instant('27/10/2026 08:00:00'));
        $this->assertSame('08:05:10',DateFormat::time('08:05:10'));
    }
    public function testInvalidCalendarDateIsRejected():void{$this->expectException(Problem::class);DateFormat::date('31/02/2026');}
    public function testNestedExportDatesAreFormatted():void{$this->assertSame(['date'=>'27/10/2026','id'=>27],DateFormat::display(['date'=>'2026-10-27','id'=>27]));}
    public function testDateFiltersRespectVietnamDayBoundaries():void{$this->assertSame('2026-10-26T17:00:00Z',DateFormat::boundary('27/10/2026'));$this->assertSame('2026-10-27T16:59:59Z',DateFormat::boundary('2026-10-27',true));}
}
