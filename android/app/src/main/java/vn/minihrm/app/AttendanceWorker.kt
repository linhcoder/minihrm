package vn.minihrm.app
import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.google.gson.JsonParser
import dagger.hilt.EntryPoint
import dagger.hilt.InstallIn
import dagger.hilt.android.EntryPointAccessors
import dagger.hilt.components.SingletonComponent
@EntryPoint @InstallIn(SingletonComponent::class) interface WorkerDependencies {fun repository():Repository}
class AttendanceWorker(context:Context,params:WorkerParameters):CoroutineWorker(context,params){
    override suspend fun doWork():Result {
        val repo=EntryPointAccessors.fromApplication(applicationContext,WorkerDependencies::class.java).repository()
        if(!repo.hasSession())return Result.failure()
        return try {
            val user=repo.get("auth/me").getAsJsonObject("user").get("id").asInt
            for(attempt in repo.db.attempts().queued(user)){
                try{val result=repo.post("attendance/attempt",JsonParser.parseString(attempt.payload).asJsonObject);repo.db.attempts().mark(attempt.key,"pending_verification","Đã gửi; trạng thái server: ${result.get("status").asString}")}
                catch(e:ApiFailure){if(e.code>=500||e.code==429)return Result.retry();repo.db.attempts().mark(attempt.key,"rejected",e.message?:"Bị từ chối")}
            };Result.success()
        }catch(_:Exception){Result.retry()}
    }
}
