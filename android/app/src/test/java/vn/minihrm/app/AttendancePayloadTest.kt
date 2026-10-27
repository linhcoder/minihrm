package vn.minihrm.app
import org.junit.Assert.*
import org.junit.Test
class AttendancePayloadTest {
    @Test fun offlineReplayPreservesOriginalKeyAndEvidence(){
        val online=AttendancePayload.create(9,"check_in","test-device")
        val offline=AttendancePayload.offline(online)
        assertEquals(online.get("idempotency_key"),offline.get("idempotency_key"))
        assertEquals(online.get("local_timestamp"),offline.get("local_timestamp"))
        assertEquals(1,offline.get("offline").asInt)
        assertFalse(offline.has("occurred_at"))
        assertFalse(offline.has("status"))
    }
    @Test fun separateAttemptsHaveDifferentKeys(){assertNotEquals(AttendancePayload.create(9,"check_in","device").get("idempotency_key"),AttendancePayload.create(9,"check_in","device").get("idempotency_key"))}
    @Test(expected=IllegalArgumentException::class) fun invalidKindRejected(){AttendancePayload.create(9,"approve","device")}
    @Test(expected=IllegalArgumentException::class) fun missingScheduleRejected(){AttendancePayload.create(0,"check_in","device")}
}
