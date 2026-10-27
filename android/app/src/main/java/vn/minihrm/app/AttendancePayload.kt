package vn.minihrm.app
import com.google.gson.JsonObject
import java.time.Instant
import java.util.UUID
/** Device time is evidence only. Server owns approved occurrence time. */
object AttendancePayload {
    fun create(schedule:Int,kind:String,device:String):JsonObject {
        require(schedule>0)
        require(kind in listOf("check_in","check_out"))
        return json("schedule_id" to schedule,"kind" to kind,"local_timestamp" to Instant.now().toString(),"idempotency_key" to UUID.randomUUID().toString(),"device_id" to device)
    }
    fun offline(payload:JsonObject):JsonObject=payload.deepCopy().apply{addProperty("offline",1)}
}
