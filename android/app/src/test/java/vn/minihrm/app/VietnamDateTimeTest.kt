package vn.minihrm.app
import org.junit.Assert.*
import org.junit.Test
class VietnamDateTimeTest {
    @Test fun utcCrossesMidnightInVietnam(){assertEquals("28/10/2026 01:05:09",VietnamDateTime.display("2026-10-27T18:05:09Z"))}
    @Test fun dateOnlyNeverShiftsDay(){assertEquals("27/10/2026",VietnamDateTime.display("2026-10-27"))}
    @Test fun midnightAndTimeSeconds(){assertEquals("27/10/2026 00:00:00",VietnamDateTime.display("2026-10-27T00:00:00+07:00"));assertEquals("08:05:00",VietnamDateTime.display("08:05"))}
    @Test fun formsSerializeCanonicalValues(){assertEquals("2024-02-29",VietnamDateTime.toApiDate("29/02/2024"));assertEquals("2026-10-27T01:00:00Z",VietnamDateTime.toApiTimestamp("27/10/2026 08:00:00"))}
    @Test(expected=java.time.format.DateTimeParseException::class) fun invalidCalendarDayRejected(){VietnamDateTime.toApiDate("31/02/2026")}
}
