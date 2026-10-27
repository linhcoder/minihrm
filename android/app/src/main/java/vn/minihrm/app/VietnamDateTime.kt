package vn.minihrm.app
import java.time.*
import java.time.format.DateTimeFormatter
import java.time.format.ResolverStyle
object VietnamDateTime {
    val zone:ZoneId=ZoneId.of("Asia/Ho_Chi_Minh")
    private val date=DateTimeFormatter.ofPattern("dd/MM/uuuu").withResolverStyle(ResolverStyle.STRICT)
    private val timestamp=DateTimeFormatter.ofPattern("dd/MM/uuuu HH:mm:ss").withResolverStyle(ResolverStyle.STRICT)
    private val time=DateTimeFormatter.ofPattern("HH:mm:ss")
    fun display(value:String):String=try{when{
        Regex("\\d{4}-\\d{2}-\\d{2}").matches(value)->LocalDate.parse(value).format(date)
        Regex("\\d{2}:\\d{2}(:\\d{2})?").matches(value)->LocalTime.parse(value).format(time)
        Regex("\\d{4}-\\d{2}-\\d{2}[T ].*").matches(value)->Instant.parse(if(Regex(".*(Z|[+-]\\d{2}:\\d{2})$").matches(value))OffsetDateTime.parse(value).toInstant().toString() else value.replace(' ','T')+"Z").atZone(zone).format(timestamp)
        else->value
    }}catch(_:Exception){value}
    fun toApiDate(value:String):String=if(value.contains('/'))LocalDate.parse(value,date).toString()else LocalDate.parse(value).toString()
    fun toApiTimestamp(value:String):String=if(value.contains('/'))LocalDateTime.parse(value,timestamp).atZone(zone).toInstant().toString()else Instant.parse(value).toString()
    fun now():String=ZonedDateTime.now(zone).format(timestamp)
}
