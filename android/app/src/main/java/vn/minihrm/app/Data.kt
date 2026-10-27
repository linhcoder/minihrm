package vn.minihrm.app

import android.app.Application
import android.content.Context
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import androidx.room.*
import androidx.work.*
import com.google.gson.JsonObject
import com.google.gson.JsonParser
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.HiltAndroidApp
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import okhttp3.OkHttpClient
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Url
import retrofit2.http.Header
import retrofit2.http.Body
import java.security.KeyStore
import java.util.concurrent.TimeUnit
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec
import javax.inject.Inject
import javax.inject.Singleton
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

@HiltAndroidApp class HrmApplication : Application()

@Singleton class SecureTokens @Inject constructor(@ApplicationContext context: Context) {
    private val prefs = context.getSharedPreferences("secure_session", Context.MODE_PRIVATE)
    private val key: SecretKey get() {
        val store = KeyStore.getInstance("AndroidKeyStore").apply { load(null) }
        return (store.getKey("hrm_tokens", null) as? SecretKey) ?: KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore").apply {
            init(KeyGenParameterSpec.Builder("hrm_tokens", KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT).setBlockModes(KeyProperties.BLOCK_MODE_GCM).setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE).build())
        }.generateKey()
    }
    private fun read(name: String): String = try {
        val raw = Base64.decode(prefs.getString(name, ""), Base64.NO_WRAP)
        if (raw.size < 13) "" else Cipher.getInstance("AES/GCM/NoPadding").run { init(Cipher.DECRYPT_MODE, key, GCMParameterSpec(128, raw.copyOfRange(0,12))); String(doFinal(raw.copyOfRange(12,raw.size))) }
    } catch (_: Exception) { "" }
    fun access() = read("access")
    fun refresh() = read("refresh")
    fun save(v: JsonObject) { for (name in listOf("access","refresh")) {
        val cipher = Cipher.getInstance("AES/GCM/NoPadding").apply { init(Cipher.ENCRYPT_MODE,key) }
        val bytes = cipher.iv + cipher.doFinal(v.get("${name}_token").asString.toByteArray())
        prefs.edit().putString(name,Base64.encodeToString(bytes,Base64.NO_WRAP)).apply()
    } }
    fun clear() { prefs.edit().clear().commit() }
}
interface HrmApi {
    @POST("auth/login") suspend fun login(@Body body: JsonObject): Response<JsonObject>
    @POST("auth/refresh") suspend fun refresh(@Body body: JsonObject): Response<JsonObject>
    @GET suspend fun get(@Url path: String,@Header("Authorization") bearer: String): Response<JsonObject>
    @POST suspend fun post(@Url path: String,@Header("Authorization") bearer: String,@Body body: JsonObject): Response<JsonObject>
}
@Entity(tableName="attendance_queue") data class QueuedAttempt(@PrimaryKey val key: String,val userId: Int,val payload: String,val state: String="queued",val message: String="",val createdAt: Long=System.currentTimeMillis())
@Dao interface AttemptDao {
    @Insert(onConflict=OnConflictStrategy.IGNORE) suspend fun add(attempt: QueuedAttempt)
    @Query("SELECT * FROM attendance_queue WHERE userId = :user ORDER BY createdAt DESC") fun observe(user:Int): Flow<List<QueuedAttempt>>
    @Query("SELECT * FROM attendance_queue WHERE state='queued' AND userId = :user ORDER BY createdAt") suspend fun queued(user:Int): List<QueuedAttempt>
    @Query("UPDATE attendance_queue SET state=:state,message=:message WHERE `key`=:key") suspend fun mark(key:String,state:String,message:String)
    @Query("DELETE FROM attendance_queue WHERE userId = :user") suspend fun clear(user:Int)
}
@Database(entities=[QueuedAttempt::class],version=1,exportSchema=false) abstract class LocalDb:RoomDatabase(){abstract fun attempts():AttemptDao}
@Module @InstallIn(SingletonComponent::class) object DataModule {
    @Provides @Singleton fun api():HrmApi = Retrofit.Builder().baseUrl(BuildConfig.API_URL).client(OkHttpClient.Builder().connectTimeout(15,TimeUnit.SECONDS).readTimeout(25,TimeUnit.SECONDS).build()).addConverterFactory(GsonConverterFactory.create()).build().create(HrmApi::class.java)
    @Provides @Singleton fun db(@ApplicationContext c:Context):LocalDb = Room.databaseBuilder(c,LocalDb::class.java,"hrm.db").build()
}
fun json(vararg pairs:Pair<String,Any?>):JsonObject = JsonObject().apply { pairs.forEach { (k,v)->when(v){null->add(k,com.google.gson.JsonNull.INSTANCE);is Number->addProperty(k,v);is Boolean->addProperty(k,v);else->addProperty(k,v.toString())} } }
class ApiFailure(val code:Int,message:String):Exception(message)
@Singleton class Repository @Inject constructor(private val api:HrmApi,private val tokens:SecureTokens,val db:LocalDb,@ApplicationContext private val context:Context) {
    private val mutex=Mutex()
    suspend fun login(email:String,password:String):JsonObject {val result=unwrap(api.login(json("email" to email,"password" to password)));tokens.save(result);return result}
    fun hasSession()=tokens.access().isNotEmpty()
    private fun unwrap(r:Response<JsonObject>):JsonObject {
        if(r.isSuccessful)return r.body()?:JsonObject()
        val message=try{JsonParser.parseString(r.errorBody()?.string()).asJsonObject.getAsJsonObject("error").get("message").asString}catch(_:Exception){"Không thể xử lý yêu cầu."}
        throw ApiFailure(r.code(),message)
    }
    private suspend fun renew()=mutex.withLock {val r=api.refresh(json("refresh_token" to tokens.refresh()));if(!r.isSuccessful){tokens.clear();throw ApiFailure(401,"Phiên đăng nhập hết hạn.")};tokens.save(unwrap(r))}
    suspend fun get(path:String):JsonObject {var r=api.get(path,"Bearer ${tokens.access()}");if(r.code()==401){renew();r=api.get(path,"Bearer ${tokens.access()}")};return unwrap(r)}
    suspend fun post(path:String,v:JsonObject):JsonObject {var r=api.post(path,"Bearer ${tokens.access()}",v);if(r.code()==401&&!path.startsWith("auth/")){renew();r=api.post(path,"Bearer ${tokens.access()}",v)};return unwrap(r)}
    suspend fun forgot(email:String):JsonObject=unwrap(api.post("auth/forgot","",json("email" to email)))
    suspend fun logout(user:Int){try{post("auth/logout",JsonObject())}finally{tokens.clear();db.attempts().clear(user);WorkManager.getInstance(context).cancelUniqueWork("attendance-sync")}}
    suspend fun queue(user:Int,payload:JsonObject):String {
        val key=payload.get("idempotency_key").asString
        // Queue records always require server verification, even if network returns shortly afterwards.
        payload.addProperty("offline",1)
        db.attempts().add(QueuedAttempt(key,user,payload.toString()))
        WorkManager.getInstance(context).enqueueUniqueWork("attendance-sync",ExistingWorkPolicy.APPEND_OR_REPLACE,OneTimeWorkRequestBuilder<AttendanceWorker>().setConstraints(Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build()).setBackoffCriteria(BackoffPolicy.EXPONENTIAL,30,TimeUnit.SECONDS).build())
        return "Đã lưu yêu cầu offline. Chờ đồng bộ và quản lý xác minh."
    }
}
