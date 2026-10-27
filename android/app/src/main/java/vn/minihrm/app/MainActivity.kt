package vn.minihrm.app

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.location.LocationManager
import android.net.wifi.WifiManager
import android.os.Build
import android.os.Bundle
import android.os.CancellationSignal
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.filled.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.core.content.ContextCompat
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.google.gson.JsonObject
import com.google.gson.JsonParser
import dagger.hilt.android.AndroidEntryPoint
import java.time.ZonedDateTime
import java.time.format.DateTimeFormatter
import kotlinx.coroutines.delay

@AndroidEntryPoint class MainActivity:ComponentActivity(){override fun onCreate(savedInstanceState:Bundle?){super.onCreate(savedInstanceState);setContent{MaterialTheme(colorScheme=lightColorScheme(primary=Color(0xff2563eb),background=Color(0xfff5f7fb),surface=Color.White)){MiniHrm()}}}}
private fun JsonObject.text(key:String):String= get(key)?.takeUnless{it.isJsonNull}?.let{if(it.isJsonPrimitive)it.asString else it.toString()}?:"—"
private val titles=mapOf("home" to "Trang chủ","schedules" to "Lịch làm việc","attendance" to "Lịch sử chấm công","timesheets" to "Bảng công","leave-requests" to "Đơn nghỉ phép","salary-advances" to "Tạm ứng lương","payslips" to "Phiếu lương","notifications" to "Thông báo","profile" to "Hồ sơ","settings" to "Cài đặt","attendance-corrections" to "Điều chỉnh công")
private val labels=mapOf("work_date" to "Ngày làm","shift_id" to "Ca làm","branch_id" to "Chi nhánh","kind" to "Loại","occurred_at" to "Giờ server","status" to "Trạng thái","check_in" to "Giờ vào","check_out" to "Giờ ra","worked_minutes" to "Phút làm","late_minutes" to "Phút trễ","overtime_minutes" to "Phút tăng ca","review_status" to "Duyệt công","start_date" to "Từ ngày","end_date" to "Đến ngày","reason" to "Lý do","days" to "Số ngày","amount" to "Số tiền","request_date" to "Ngày đề nghị","gross" to "Tổng thu nhập","net" to "Thực lĩnh ước tính","advances" to "Tạm ứng","deductions" to "Khấu trừ","full_name" to "Họ và tên","email" to "Email","phone" to "Điện thoại","job_title" to "Chức danh","hire_date" to "Ngày vào làm","code" to "Mã nhân viên","title" to "Tiêu đề","body" to "Nội dung","timesheet_id" to "Mã bảng công","new_values" to "Giờ mới (JSON giờ UTC)","leave_type_id" to "Mã loại phép","payroll_period_id" to "Mã kỳ lương","half_day" to "Nửa ngày (0/1)")
private val statuses=mapOf("pending" to "Chờ duyệt","approved" to "Đã duyệt","rejected" to "Từ chối","verified" to "Đã xác minh","pending_verification" to "Chờ xác minh","check_in" to "Vào ca","check_out" to "Ra ca","present" to "Đúng giờ","late" to "Đi trễ","absent" to "Vắng","missing_check_out" to "Thiếu giờ ra","published" to "Đã phát hành","scheduled" to "Đã xếp ca","active" to "Đang làm việc")
@OptIn(ExperimentalMaterial3Api::class)
@Composable fun MiniHrm(vm:MainViewModel=hiltViewModel()){
    val s by vm.state.collectAsStateWithLifecycle();val snackbar=remember{SnackbarHostState()}
    LaunchedEffect(s.message){if(s.message.isNotEmpty())snackbar.showSnackbar(s.message)}
    var menu by remember{mutableStateOf(false)};var request by remember{mutableStateOf(false)}
    Scaffold(snackbarHost={SnackbarHost(snackbar)},topBar={if(s.loggedIn)TopAppBar(title={Text(titles[s.resource]?:"Mini HRM",fontWeight=FontWeight.Bold)},navigationIcon={if(s.resource!="home")IconButton(onClick={vm.open("home")}){Icon(Icons.AutoMirrored.Filled.ArrowBack,"Về trang chủ")}},actions={IconButton(onClick={menu=!menu}){Icon(Icons.Default.Menu,"Mở menu")};DropdownMenu(expanded=menu,onDismissRequest={menu=false}){titles.forEach{(key,title)->DropdownMenuItem(text={Text(title)},onClick={menu=false;vm.open(key)})}}})},bottomBar={if(s.loggedIn)NavigationBar{listOf("home" to Icons.Default.Home,"schedules" to Icons.Default.DateRange,"notifications" to Icons.Default.Notifications,"profile" to Icons.Default.Person).forEach{(key,icon)->NavigationBarItem(selected=s.resource==key,onClick={vm.open(key)},icon={Icon(icon,titles[key])},label={Text(titles[key]?:"")})}}}){padding->
        Column(Modifier.fillMaxSize().padding(padding).background(MaterialTheme.colorScheme.background).verticalScroll(rememberScrollState()).padding(20.dp),verticalArrangement=Arrangement.spacedBy(16.dp)){
            if(s.busy)LinearProgressIndicator(Modifier.fillMaxWidth())
            if(!s.loggedIn)Login(s.busy,vm) else when(s.resource){
                "home"->Home(s,vm)
                "settings"->{Text("Quyền riêng tư",fontSize=22.sp,fontWeight=FontWeight.Bold);Text("Vị trí chỉ được thu thập lúc bạn chấm công nếu chính sách yêu cầu. Không theo dõi nền. Vị trí giả có thể được gắn cờ nhưng không thể loại bỏ hoàn toàn.");Text("Yêu cầu truy cập hoặc ẩn danh dữ liệu: liên hệ quản trị công ty. Hồ sơ lương được giữ theo nghĩa vụ lưu trữ.");Text("Máy chủ: ${BuildConfig.API_URL}",fontSize=12.sp);Button(onClick={vm.logout()},enabled=!s.busy){Icon(Icons.AutoMirrored.Filled.Logout,null);Text("Đăng xuất")}}
                else->{
                    if(s.resource in listOf("leave-requests","salary-advances","attendance-corrections"))Button(onClick={request=true},enabled=!s.busy){Icon(Icons.Default.Add,null);Text("Gửi yêu cầu")}
                    if(s.resource=="payslips")Text("Ước tính chưa hoàn tất thuế và bảo hiểm bắt buộc.",color=Color(0xffb7791f),fontSize=12.sp)
                    if(s.rows.isEmpty()&&!s.busy)Card{Text("Chưa có dữ liệu.",Modifier.padding(24.dp))}
                    s.rows.forEach{row->Record(row,s.resource){vm.read(row.get("id").asInt)}}
                    OutlinedButton(onClick={vm.open(s.resource)},enabled=!s.busy){Icon(Icons.Default.Refresh,null);Text("Làm mới")}
                }
            }
        }
    }
    if(request)RequestDialog(s.resource,onDismiss={request=false},onSubmit={v->vm.submit(s.resource,v);request=false})
}
@Composable private fun Login(busy:Boolean,vm:MainViewModel){var email by remember{mutableStateOf("")};var password by remember{mutableStateOf("")};Spacer(Modifier.height(45.dp));Text("mini hrm",fontWeight=FontWeight.ExtraBold,fontSize=35.sp,color=MaterialTheme.colorScheme.primary);Text("Chào mừng trở lại",fontWeight=FontWeight.Bold,fontSize=25.sp);Text("Chấm công và theo dõi công việc của bạn.",color=Color.Gray);OutlinedTextField(value=email,onValueChange={email=it},label={Text("Email")},singleLine=true,modifier=Modifier.fillMaxWidth());OutlinedTextField(value=password,onValueChange={password=it},label={Text("Mật khẩu")},singleLine=true,visualTransformation=PasswordVisualTransformation(),modifier=Modifier.fillMaxWidth());Button(onClick={vm.login(email,password)},enabled=!busy&&email.isNotBlank()&&password.isNotBlank(),modifier=Modifier.fillMaxWidth().height(50.dp)){Text("Đăng nhập")};TextButton(onClick={vm.forgot(email)},enabled=!busy&&email.isNotBlank()){Text("Quên mật khẩu?")}}
@Composable private fun Home(s:AppState,vm:MainViewModel){
    var time by remember{mutableStateOf(ZonedDateTime.now())};LaunchedEffect(Unit){while(true){time=ZonedDateTime.now();delay(1000)}}
    val home=s.home;Text("Xin chào, ${s.me?.getAsJsonObject("user")?.text("full_name")?:"bạn"} 👋",fontSize=23.sp,fontWeight=FontWeight.Bold);Text(s.me?.getAsJsonObject("tenant")?.text("name")?:"",color=Color.Gray)
    Text(time.format(DateTimeFormatter.ofPattern("HH:mm:ss · dd/MM/yyyy")),fontSize=16.sp)
    val schedules=home?.getAsJsonArray("schedules")?.map{it.asJsonObject}?:emptyList()
    if(schedules.isEmpty())Card{Text("Hôm nay bạn chưa được xếp ca.",Modifier.padding(22.dp))}
    schedules.forEach{schedule->AttendanceCard(schedule,s.busy){kind,loc,wifi->vm.attendance(schedule.get("id").asInt,kind,loc,wifi)}}
    Text("Truy cập nhanh",fontWeight=FontWeight.Bold,fontSize=17.sp)
    listOf("leave-requests","timesheets","salary-advances","payslips","attendance-corrections").forEach{r->OutlinedButton(onClick={vm.open(r)},modifier=Modifier.fillMaxWidth()){Text(titles[r]?:r)}}
    if(s.queued.isNotEmpty()){Text("Yêu cầu lưu trên thiết bị",fontWeight=FontWeight.Bold);s.queued.forEach{q->Card{Column(Modifier.padding(15.dp)){Text(if(q.state=="queued")"Đang chờ đồng bộ" else if(q.state=="pending_verification")"Đã đồng bộ · Chờ xác minh" else "Không được chấp nhận");Text(q.message,fontSize=11.sp)}}}}
    Text("Chấm công gần đây",fontWeight=FontWeight.Bold);home?.getAsJsonArray("recent")?.forEach{r->Record(r.asJsonObject,"attendance") {}}
}
@Composable private fun AttendanceCard(schedule:JsonObject,busy:Boolean,onAttempt:(String,JsonObject?,String?)->Unit){
    val context=LocalContext.current;val branch=schedule.getAsJsonObject("branch");val policy=branch?.text("attendance_policy")?:"basic";var kind by remember{mutableStateOf("check_in")};var message by remember{mutableStateOf("")};var acquiring by remember{mutableStateOf(false)}
    val locationManager=context.getSystemService(LocationManager::class.java)
    val cancel=remember{CancellationSignal()};DisposableEffect(Unit){onDispose{cancel.cancel()}}
    fun acquire(){
        if(policy=="basic"){onAttempt(kind,null,null);return}
        if(ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_COARSE_LOCATION)!=PackageManager.PERMISSION_GRANTED){message="Cần quyền vị trí khi chấm công. Bạn có thể gửi điều chỉnh công nếu không cấp quyền.";return}
        if(policy=="wifi"){
            @Suppress("DEPRECATION") val bssid=(context.applicationContext.getSystemService(Context.WIFI_SERVICE) as WifiManager).connectionInfo?.bssid
            if(bssid==null||bssid=="02:00:00:00:00:00"){message="Android không cung cấp Wi-Fi. Hãy gửi yêu cầu điều chỉnh công.";return};onAttempt(kind,null,bssid);return
        }
        acquiring=true
        try {
            if(Build.VERSION.SDK_INT>=30)locationManager.getCurrentLocation(if(ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_FINE_LOCATION)==PackageManager.PERMISSION_GRANTED)LocationManager.GPS_PROVIDER else LocationManager.NETWORK_PROVIDER,cancel,context.mainExecutor){loc->acquiring=false;if(loc==null){message="Không lấy được vị trí. Kiểm tra GPS hoặc gửi điều chỉnh công."}else{@Suppress("DEPRECATION") val mock=if(Build.VERSION.SDK_INT>=31)loc.isMock else loc.isFromMockProvider;onAttempt(kind,json("latitude" to loc.latitude,"longitude" to loc.longitude,"accuracy" to loc.accuracy.toInt(),"mock_location" to if(mock)1 else 0),null)}}
            else {acquiring=false;message="Thiết bị Android cũ: vui lòng gửi yêu cầu điều chỉnh công theo chính sách công ty."}
        }catch(_:SecurityException){acquiring=false;message="Không có quyền vị trí."}catch(_:Exception){acquiring=false;message="Vị trí hiện không khả dụng."}
    }
    val permission=rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()){grants->if(grants.values.any{it})acquire()else{message="Bạn đã từ chối quyền vị trí. Có thể gửi điều chỉnh công."}}
    Card(shape=RoundedCornerShape(16.dp),colors=CardDefaults.cardColors(containerColor=Color.White)){Column(Modifier.padding(20.dp),verticalArrangement=Arrangement.spacedBy(12.dp)){
        Text(schedule.text("shift_name"),fontWeight=FontWeight.Bold,fontSize=20.sp);Text("${schedule.text("start_time")} – ${schedule.text("end_time")} · ${branch?.text("name")?:""}",color=Color.Gray,fontSize=13.sp)
        Text("Lần gần nhất: ${statuses[schedule.text("last_kind")]?:schedule.text("last_kind")}",fontSize=12.sp)
        if(policy!="basic")Text(if(policy=="gps")"Vị trí chỉ dùng lúc chấm công trong bán kính chi nhánh. Vị trí gần đúng có thể cần quản lý xác minh." else "Wi-Fi cần quyền vị trí theo phiên bản Android; BSSID chỉ là bằng chứng hỗ trợ, cần xác minh.",fontSize=11.sp,color=Color.Gray)
        Row(horizontalArrangement=Arrangement.spacedBy(10.dp)){listOf("check_in" to "Vào ca","check_out" to "Ra ca").forEach{(k,t)->Button(onClick={kind=k;if(policy!="basic"&&ContextCompat.checkSelfPermission(context,Manifest.permission.ACCESS_COARSE_LOCATION)!=PackageManager.PERMISSION_GRANTED)permission.launch(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION,Manifest.permission.ACCESS_COARSE_LOCATION))else acquire()},enabled=!busy&&!acquiring,modifier=Modifier.weight(1f)){Text(t)}}}
        if(acquiring)LinearProgressIndicator(Modifier.fillMaxWidth());if(message.isNotBlank())Text(message,color=MaterialTheme.colorScheme.error,fontSize=12.sp)
    }}
}
@Composable private fun Record(row:JsonObject,resource:String,onRead:()->Unit){var expanded by remember{mutableStateOf(false)};val keys=when(resource){"schedules"->listOf("work_date","shift_id","branch_id","status");"attendance"->listOf("kind","occurred_at","status");"timesheets"->listOf("work_date","check_in","check_out","worked_minutes","late_minutes","review_status");"leave-requests"->listOf("start_date","end_date","days","reason","status");"salary-advances"->listOf("amount","request_date","reason","status");"payslips"->listOf("gross","advances","deductions","net","status");"notifications"->listOf("title","body");"profile"->listOf("full_name","code","email","phone","job_title","hire_date");else->listOf("reason","status")}
    Card(colors=CardDefaults.cardColors(containerColor=Color.White),modifier=Modifier.fillMaxWidth()){Column(Modifier.padding(18.dp),verticalArrangement=Arrangement.spacedBy(9.dp)){keys.forEach{k->Row(Modifier.fillMaxWidth(),horizontalArrangement=Arrangement.SpaceBetween){Text(labels[k]?:k,color=Color.Gray,fontSize=12.sp,modifier=Modifier.weight(1f));Text(statuses[row.text(k)]?:row.text(k),fontSize=13.sp,modifier=Modifier.weight(1.4f))}}
        if(resource=="payslips"){TextButton(onClick={expanded=!expanded}){Text("Chi tiết tính lương")};if(expanded)Text(row.text("breakdown"),fontSize=11.sp)}
        if(resource=="notifications"&&row.text("read_at")=="—")TextButton(onClick=onRead){Text("Đánh dấu đã đọc")}
    }}
}
@Composable private fun RequestDialog(resource:String,onDismiss:()->Unit,onSubmit:(JsonObject)->Unit){val fields=when(resource){"leave-requests"->listOf("leave_type_id","start_date","end_date","half_day","reason");"salary-advances"->listOf("amount","request_date","payroll_period_id","reason");else->listOf("timesheet_id","new_values","reason")};val values=remember{mutableStateMapOf<String,String>()};var error by remember{mutableStateOf("")};AlertDialog(onDismissRequest=onDismiss,title={Text("Gửi yêu cầu")},text={Column(Modifier.verticalScroll(rememberScrollState()),verticalArrangement=Arrangement.spacedBy(10.dp)){Text("Ngày: YYYY-MM-DD. Mã loại phép và kỳ lương do quản lý cung cấp.",fontSize=11.sp);fields.forEach{k->OutlinedTextField(value=values[k]?:"",onValueChange={values[k]=it},label={Text(labels[k]?:k)},singleLine=k!="new_values",modifier=Modifier.fillMaxWidth())};if(error.isNotBlank())Text(error,color=MaterialTheme.colorScheme.error)}},confirmButton={TextButton(onClick={try{val v=JsonObject();fields.forEach{k->val value=values[k]?:"";if(value.isNotBlank()){if(k=="new_values")v.add(k,JsonParser.parseString(value))else v.addProperty(k,value)}};if((values["reason"]?:"").isBlank()){error="Cần nhập lý do."}else onSubmit(v)}catch(_:Exception){error="JSON không hợp lệ."}}){Text("Gửi")}},dismissButton={TextButton(onClick=onDismiss){Text("Hủy")}})}
