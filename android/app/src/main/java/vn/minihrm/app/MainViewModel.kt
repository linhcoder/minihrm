package vn.minihrm.app
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.google.gson.JsonObject
import dagger.hilt.android.lifecycle.HiltViewModel
import javax.inject.Inject
import javax.inject.Provider
import kotlinx.coroutines.launch
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.coroutines.flow.*
import java.time.Instant
import java.util.UUID

data class AppState(val loggedIn:Boolean=false,val busy:Boolean=false,val message:String="",val me:JsonObject?=null,val home:JsonObject?=null,val resource:String="home",val rows:List<JsonObject> = emptyList(),val queued:List<QueuedAttempt> = emptyList())
@HiltViewModel class MainViewModel @Inject constructor(private val repositoryProvider:Provider<Repository>):ViewModel(){
    private val repo:Repository by lazy { repositoryProvider.get() }
    private val _state=MutableStateFlow(AppState())
    val state=_state.asStateFlow()
    private var queueJob:kotlinx.coroutines.Job?=null
    init{run{if(repo.hasSession()){_state.update{it.copy(loggedIn=true)};loadInternal()}}}
    private fun run(block:suspend()->Unit){viewModelScope.launch{_state.update{it.copy(busy=true,message="")};try{withContext(Dispatchers.IO){block()}}catch(e:Exception){_state.update{it.copy(message=e.message?:"Không thể kết nối.",loggedIn=if(e is ApiFailure&&e.code==401)false else it.loggedIn)}}finally{_state.update{it.copy(busy=false)}}}}
    fun login(email:String,password:String)=run{repo.login(email,password);_state.update{it.copy(loggedIn=true)};loadInternal()}
    fun forgot(email:String)=run{val r=repo.forgot(email);_state.update{it.copy(message=r.get("message").asString)}}
    fun load()=run{loadInternal()}
    private suspend fun loadInternal(){
        val me=repo.get("auth/me");val home=repo.get("mobile-home");_state.update{it.copy(me=me,home=home)}
        queueJob?.cancel();queueJob=viewModelScope.launch{repo.db.attempts().observe(me.getAsJsonObject("user").get("id").asInt).collect{queue->_state.update{it.copy(queued=queue)}}}
    }
    fun open(resource:String)=run{if(resource=="home"){loadInternal();_state.update{it.copy(resource=resource)};return@run};val response=if(resource=="profile")repo.get("employees")else repo.get("$resource?limit=100");_state.update{it.copy(resource=resource,rows=response.getAsJsonArray("data")?.map{r->r.asJsonObject}?:emptyList())}}
    fun submit(resource:String,values:JsonObject)=run{repo.post(resource,values);val response=repo.get("$resource?limit=100");_state.update{it.copy(rows=response.getAsJsonArray("data").map{r->r.asJsonObject},message="Đã gửi yêu cầu. Chờ quản lý phê duyệt.")}}
    fun read(id:Int)=run{repo.post("notifications/$id/read",JsonObject());val r=repo.get("notifications?limit=100");_state.update{it.copy(rows=r.getAsJsonArray("data").map{v->v.asJsonObject})}}
    fun attendance(schedule:Int,kind:String,location:JsonObject?=null,wifi:String?=null)=run{
        val user=_state.value.me?.getAsJsonObject("user")?.get("id")?.asInt?:return@run
        val payload=AttendancePayload.create(schedule,kind,"android-${android.os.Build.VERSION.SDK_INT}")
        location?.entrySet()?.forEach{(k,v)->payload.add(k,v)};if(wifi!=null)payload.addProperty("wifi_bssid",wifi)
        try{val r=repo.post("attendance/attempt",payload);_state.update{it.copy(message=if(r.get("status").asString=="verified")"Chấm công thành công." else "Đã gửi; chờ quản lý xác minh.")};val home=repo.get("mobile-home");_state.update{it.copy(home=home)}}
        catch(e:java.io.IOException){val message=repo.queue(user,payload);_state.update{it.copy(message=message)}}
    }
    fun message(s:String){_state.update{it.copy(message=s)}}
    fun logout()=run{repo.logout(_state.value.me?.getAsJsonObject("user")?.get("id")?.asInt?:0);queueJob?.cancel();_state.value=AppState()}
}
