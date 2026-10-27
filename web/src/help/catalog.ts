export const GUIDE_VERSION=1;
export type Audience={userId:number;tenantId:number|null;role:string;superUser:boolean;permissions:string[]};
export type TopicId='start'|'people'|'time'|'requests'|'payroll'|'reports'|'mobile'|'platform';
export const topics:{id:TopicId;title:string;description:string;icon:string}[]=[
 {id:'start',title:'Làm quen Mini HRM',description:'Bắt đầu từ những thao tác cơ bản.',icon:'sparkles'},
 {id:'people',title:'Nhân sự & tổ chức',description:'Hồ sơ, tài khoản, phòng ban và chi nhánh.',icon:'users'},
 {id:'time',title:'Lịch làm & bảng công',description:'Xếp ca, kiểm tra và duyệt thời gian làm việc.',icon:'calendar'},
 {id:'requests',title:'Nghỉ phép & tạm ứng',description:'Gửi yêu cầu và theo dõi kết quả xử lý.',icon:'leaf'},
 {id:'payroll',title:'Lương & phiếu lương',description:'Tính, kiểm tra và phát hành lương.',icon:'wallet'},
 {id:'reports',title:'Báo cáo & cài đặt',description:'Tra cứu dữ liệu, xuất file và phân quyền.',icon:'chart'},
 {id:'mobile',title:'Chấm công trên Android',description:'Vào ca, ra ca và xử lý khi mất kết nối.',icon:'phone'},
 {id:'platform',title:'Quản trị hệ thống',description:'Quản lý công ty và theo dõi hoạt động nền tảng.',icon:'shield'},
];
export type Article={id:string;title:string;summary:string;topic:TopicId;audience:'all'|'company'|'employee'|'platform';permission?:string;roles?:string[];minutes:number;tags:string[];steps:{title:string;body:string}[];tip:string;related:string[];action:{label:string;path:string};illustration:'portal'|'mobile'|'flow'};
type Options=Partial<Pick<Article,'audience'|'permission'|'roles'|'minutes'|'illustration'|'tags'>>;
const make=(id:string,title:string,topic:TopicId,summary:string,steps:[string,string][],tip:string,related:string[],path:string,options:Options={}):Article=>({id,title,topic,summary,steps:steps.map(([title,body])=>({title,body})),tip,related,action:{label:'Mở chức năng này',path},audience:'company',minutes:3,tags:[],illustration:'portal',...options});
export const articles:Article[]=[
 make('bat-dau','Bắt đầu trong 5 phút','start','Hiểu cách đi từ hồ sơ nhân viên đến bảng công và phiếu lương.',[
  ['Làm quen với màn hình tổng quan','Xem số nhân viên, tình hình chấm công và các yêu cầu đang chờ. Số liệu thuộc công ty và phạm vi bạn được cấp quyền.'],
  ['Chọn công việc cần làm','Dùng menu bên trái để mở nhóm chức năng. Trên điện thoại, bấm biểu tượng ba gạch để mở menu.'],
  ['Đi theo trình tự phù hợp','Người quản lý thường tạo nhân viên → tạo ca → xếp lịch → kiểm tra công. Nhân viên thường xem lịch → chấm công Android → theo dõi yêu cầu.'],
  ['Mở lại trợ giúp khi cần','Bấm biểu tượng dấu hỏi ở thanh trên hoặc chọn Hướng dẫn sử dụng. Có thể xem lại tour bất kỳ lúc nào.'],
 ],'Tour chỉ giới thiệu vị trí và cách sử dụng; bạn sẽ tự xác nhận khi tạo hoặc thay đổi dữ liệu.',['dieu-huong','cham-cong-android','them-nhan-vien'],'home',{audience:'all',tags:['bắt đầu','tour','lần đầu','onboarding'],illustration:'flow'}),
 make('dieu-huong','Tìm kiếm, lọc và xem chi tiết','start','Tìm đúng bản ghi mà không phải dò từng dòng.',[
  ['Mở nhóm chức năng','Chọn chức năng trong menu. Các mục và thao tác có thể khác nhau theo vai trò của bạn.'],
  ['Nhập từ khóa hoặc khoảng ngày','Dùng ô Tìm kiếm để tra tên, mã hoặc nội dung. Khi có bộ lọc ngày, nhập dd/mm/yyyy, ví dụ 27/10/2026.'],
  ['Mở Chi tiết','Bấm Chi tiết trên dòng cần xem. Đóng hộp thoại để quay lại danh sách và bộ lọc hiện tại.'],
  ['Chuyển trang khi cần','Dùng mũi tên ở cuối bảng. Nếu chưa có dữ liệu, kiểm tra lại bộ lọc trước khi tạo bản ghi mới.'],
 ],'Ngày giờ hiển thị theo Việt Nam: 27/10/2026 08:00:00. Một số màn hình chỉ hiển thị ngày hoặc giờ khi phù hợp.',['bat-dau','thong-bao'],'home',{audience:'all',tags:['tìm kiếm','bộ lọc','ngày','danh sách']}),
 make('thong-bao','Đọc và theo dõi thông báo','start','Không bỏ lỡ kết quả xử lý yêu cầu hoặc phiếu lương mới.',[
  ['Mở biểu tượng chuông','Bấm chuông trên thanh trên hoặc chọn Thông báo trong menu công ty.'],
  ['Đọc nội dung cập nhật','Xem tiêu đề, nội dung và ngày tạo để biết yêu cầu nào vừa được xử lý.'],
  ['Đánh dấu đã đọc','Bấm Đánh dấu đã đọc trên thông báo chưa đọc.'],
  ['Mở chức năng liên quan','Vào Đơn nghỉ phép, Điều chỉnh công hoặc Phiếu lương để xem bản ghi đầy đủ.'],
 ],'Thông báo đẩy chỉ nhắc có cập nhật; hãy đăng nhập để xem thông tin chi tiết.',['xin-nghi-phep','xem-phieu-luong'],'/notifications',{tags:['chuông','đã đọc','cập nhật']}),
 make('them-nhan-vien','Thêm một nhân viên mới','people','Tạo hồ sơ để bắt đầu xếp lịch và ghi nhận công.',[
  ['Mở Nhân viên → Thêm mới','Vào mục Nhân viên và bấm nút Thêm mới ở góc trên.'],
  ['Nhập thông tin bắt buộc','Điền mã, họ tên, email và ngày vào làm. Chọn phòng ban, chi nhánh và loại hợp đồng khi đã có dữ liệu tổ chức.'],
  ['Kiểm tra rồi lưu','Kiểm tra email, mã nhân viên và ngày dd/mm/yyyy. Bấm Lưu thông tin. Các trường lương cần quyền quản lý lương.'],
  ['Kiểm tra hồ sơ vừa tạo','Tìm tên trong danh sách và mở Chi tiết. Bạn có thể tải ảnh đại diện hoặc gửi lời mời tài khoản.'],
 ],'Mã nhân viên cần duy nhất trong công ty. Chưa cần mời tài khoản ngay nếu bạn đang chuẩn bị hồ sơ.',['nhap-excel','moi-tai-khoan','phong-ban-chi-nhanh'],'/employees',{permission:'people',tags:['thêm người','hồ sơ','nhân viên','email']}),
 make('nhap-excel','Nhập danh sách nhân viên từ Excel','people','Thêm nhiều hồ sơ bằng mẫu có sẵn và kiểm tra lỗi theo dòng.',[
  ['Tải mẫu nhập','Mở Nhân viên và bấm Tải mẫu nhập để lấy đúng tên cột.'],
  ['Điền danh sách vào file','Giữ nguyên hàng tiêu đề. Điền mã, họ tên, email, ngày vào làm; ngày có thể nhập 27/10/2026. ID phòng ban/chi nhánh phải thuộc công ty của bạn.'],
  ['Chọn Nhập Excel','Tải file .xlsx lên; mỗi lần tối đa 500 dòng, dung lượng tối đa 2 MB.'],
  ['Sửa lỗi rồi nhập lại nếu cần','Nếu có lỗi, đọc số dòng và trường bị báo lỗi. Lần nhập lỗi được hoàn tác, không thêm một phần danh sách.'],
 ],'Đừng nhập lại nguyên danh sách đã thành công: mã trùng sẽ bị từ chối. Trường lương trong file cần quyền lương.',['them-nhan-vien','phong-ban-chi-nhanh'],'/employees',{permission:'people',minutes:4,tags:['xlsx','excel','import','hàng loạt']}),
 make('phong-ban-chi-nhanh','Thiết lập phòng ban và chi nhánh','people','Sắp xếp nhân sự theo nơi làm việc và cấu hình cách chấm công.',[
  ['Tạo phòng ban hoặc chi nhánh','Mở Phòng ban hoặc Chi nhánh và chọn Thêm mới.'],
  ['Nhập thông tin phù hợp','Phòng ban có tên và người quản lý. Chi nhánh có địa chỉ, múi giờ và chính sách chấm công.'],
  ['Cấu hình chấm công nếu cần','Nếu dùng GPS, nhập tọa độ và bán kính cho phép. Wi-Fi dùng thông tin BSSID và vẫn cần xác minh.'],
  ['Gán nhân viên và lịch làm','Sửa hồ sơ nhân viên để chọn tổ chức, rồi chọn đúng chi nhánh khi xếp lịch.'],
 ],'Chính sách chấm công áp dụng theo chi nhánh của ca được xếp, kể cả khi nhân viên làm tạm ở nơi khác.',['them-nhan-vien','xep-lich','cham-cong-android'],'/branches',{permission:'people',tags:['phòng ban','chi nhánh','gps','bán kính','địa điểm']}),
 make('moi-tai-khoan','Mời nhân viên đăng nhập','people','Liên kết hồ sơ với tài khoản để nhân viên dùng ứng dụng.',[
  ['Kiểm tra email hồ sơ','Mở Nhân viên → Chi tiết và xác nhận email của người nhận.'],
  ['Bấm Mời','Trên dòng nhân viên chưa có tài khoản, bấm Mời để tạo lời mời đặt mật khẩu.'],
  ['Nhân viên đặt mật khẩu','Người nhận mở liên kết trong email và đặt mật khẩu tối thiểu 12 ký tự. Liên kết có thời hạn.'],
  ['Đăng nhập và kiểm tra','Nhân viên đăng nhập bằng email được mời, xem đúng công ty, lịch làm và hồ sơ của mình.'],
 ],'Nếu chưa nhận được email, nhờ quản trị kiểm tra cấu hình gửi thư; không tạo hồ sơ thứ hai chỉ để đổi tài khoản.',['them-nhan-vien','cham-cong-android','phan-quyen'],'/employees',{permission:'invite',tags:['mời','tài khoản','đăng nhập','mật khẩu']}),
 make('luu-tru-nhan-vien','Ngừng hoạt động hoặc lưu trữ nhân viên','people','Xử lý hồ sơ nghỉ việc và giữ lại lịch sử cần thiết.',[
  ['Mở đúng hồ sơ','Tìm nhân viên bằng mã và đối chiếu Chi tiết trước khi thao tác.'],
  ['Chọn trạng thái phù hợp','Dùng Ngừng để ngừng hoạt động; có thể Kích hoạt lại khi phù hợp.'],
  ['Lưu trữ khi cần','Bấm Lưu trữ và xác nhận nếu hồ sơ không còn dùng trong danh sách hoạt động.'],
  ['Giữ lịch sử công và lương','Lưu trữ không xóa các bản ghi công và lương đã lưu. Xem nhật ký nếu cần đối chiếu thay đổi.'],
 ],'Lưu trữ là thao tác với hồ sơ nhân sự, không thay thế quy trình xử lý dữ liệu cá nhân.',['quyen-rieng-tu','them-nhan-vien'],'/employees',{permission:'people',tags:['nghỉ việc','ngừng','lưu trữ','khôi phục']}),
 make('tao-ca','Tạo ca làm việc','time','Khai báo giờ vào, giờ ra, giờ nghỉ và cách ghi nhận đi trễ.',[
  ['Mở Ca làm việc → Thêm mới','Đặt tên dễ nhận biết như Ca sáng hoặc Ca tối.'],
  ['Nhập giờ và khoảng nghỉ','Nhập giờ dạng 08:00:00, giờ kết thúc và số phút nghỉ. Đánh dấu cấu hình nghỉ hưởng lương nếu công ty áp dụng.'],
  ['Kiểm tra chính sách ca','Thiết lập thời gian cho phép đi trễ, khung giờ chấm công, ngày áp dụng và hiệu lực.'],
  ['Lưu rồi xếp lịch','Tạo ca chưa tự phân cho nhân viên. Mở Lịch làm việc để gán người và ngày làm.'],
 ],'Giờ ra không lớn hơn giờ vào được hiểu là ca qua đêm. Hãy kiểm tra múi giờ và giờ nghỉ trước khi dùng.',['xep-lich','tong-hop-cong'],'/shifts',{permission:'schedule',tags:['ca sáng','ca tối','qua đêm','giờ nghỉ','đi trễ']}),
 make('xep-lich','Xếp lịch và sao chép tuần','time','Gán đúng người, ca và chi nhánh cho từng ngày làm việc.',[
  ['Mở Lịch làm việc','Dùng khoảng ngày để xem tuần hoặc tháng cần sắp xếp; có thể đổi giữa lịch và bảng.'],
  ['Thêm một lịch làm','Chọn nhân viên, ca làm, chi nhánh và ngày dd/mm/yyyy rồi lưu.'],
  ['Kiểm tra xung đột','Nếu hệ thống báo trùng giờ, đối chiếu các ca của nhân viên, kể cả ca qua nửa đêm.'],
  ['Sao chép khi lịch lặp lại','Bấm Sao chép tuần trước và nhập ngày đầu của tuần nguồn. Hệ thống chép 7 ngày đó sang tuần kế tiếp, có kiểm tra trùng ca.'],
 ],'Hãy tạo ca trước khi xếp lịch. Đổi chi nhánh trong lịch cũng đổi chính sách xác minh chấm công của ca đó.',['tao-ca','cham-cong-android','dieu-chinh-cong'],'/schedules',{permission:'schedule',minutes:4,tags:['lịch','tuần','tháng','sao chép','trùng ca']}),
 make('xem-lich-ca-nhan','Xem lịch làm của tôi','time','Biết ngày nào đi làm, ca nào và tại chi nhánh nào.',[
  ['Mở Lịch làm việc','Trên web dùng menu; trên Android bấm Lịch làm việc ở thanh dưới.'],
  ['Chọn khoảng ngày cần xem','Nhập ngày theo dd/mm/yyyy trên web. Android hiển thị danh sách lịch cá nhân.'],
  ['Đối chiếu ca và chi nhánh','Kiểm tra ca được phân và địa điểm; ca qua đêm có thể kết thúc vào ngày kế tiếp.'],
  ['Báo quản lý khi có sai lệch','Nhờ quản lý điều chỉnh lịch trước khi chấm công. Nhân viên không tự sửa lịch đã được phân.'],
 ],'Nếu không thấy lịch, hãy kiểm tra khoảng ngày và hỏi quản lý xem bạn đã được xếp ca chưa.',['cham-cong-android','dieu-chinh-cong'],'/schedules',{audience:'employee',tags:['lịch cá nhân','ca của tôi']}),
 make('tong-hop-cong','Tổng hợp và duyệt bảng công','time','Kiểm tra dữ liệu trước khi dùng để tính lương.',[
  ['Mở Bảng công và chọn ngày','Chọn khoảng ngày cần kiểm tra; mỗi lần tổng hợp tối đa 62 ngày.'],
  ['Bấm Tổng hợp công','Hệ thống tạo hoặc cập nhật bảng công từ các lịch và sự kiện chấm công.'],
  ['Xử lý các cảnh báo','Kiểm tra thiếu giờ ra, đi trễ, công offline chờ xác minh hoặc yêu cầu điều chỉnh. Công đang trong ca chưa đủ điều kiện duyệt.'],
  ['Duyệt các dòng đã đúng','Bấm Duyệt cho các dòng hợp lệ. Chỉ bảng công đã duyệt mới được dùng tính lương.'],
 ],'Không duyệt để bỏ qua cảnh báo. Nếu công đã được dùng tính lương, cần quy trình điều chỉnh phù hợp.',['dieu-chinh-cong','lap-bang-luong','cham-cong-offline'],'/timesheets',{permission:'attendance',minutes:4,tags:['tổng hợp','duyệt công','thiếu giờ ra','bảng công']}),
 make('dieu-chinh-cong','Gửi và xử lý điều chỉnh chấm công','time','Sửa giờ vào/ra bị thiếu hoặc sai bằng yêu cầu có lý do.',[
  ['Xác định bảng công cần sửa','Xem ngày làm và mã bảng công ở màn hình Bảng công hoặc Chi tiết.'],
  ['Tạo yêu cầu điều chỉnh','Mở Điều chỉnh công → Thêm mới. Chọn bảng công, nhập giờ mới và lý do. Trên Android dùng Gửi yêu cầu.'],
  ['Nhập đúng ngày giờ','Dùng dd/mm/yyyy HH:mm:ss theo giờ Việt Nam. Điền check_in và check_out trong phần giờ mới.'],
  ['Chờ quản lý kiểm tra','Quản lý đối chiếu dữ liệu cũ và lý do rồi duyệt hoặc từ chối. Sau điều chỉnh, bảng công vẫn cần được xem xét.'],
 ],'Ví dụ giờ vào: 27/10/2026 08:00:00. Không sửa trực tiếp công đã dùng cho kỳ lương được tính; liên hệ người phụ trách lương.',['tong-hop-cong','cham-cong-offline'],'/attendance-corrections',{tags:['quên chấm công','sửa công','giờ ra','điều chỉnh']}),
 make('xin-nghi-phep','Gửi đơn nghỉ phép','requests','Chọn thời gian nghỉ, nêu lý do và theo dõi kết quả.',[
  ['Mở Đơn nghỉ phép','Bấm Thêm mới trên web hoặc Gửi yêu cầu trên Android.'],
  ['Chọn loại phép và thời gian','Chọn loại phép đang áp dụng, nhập ngày bắt đầu/kết thúc dạng dd/mm/yyyy. Nửa ngày chỉ dùng cho một ngày.'],
  ['Nhập lý do rồi gửi','Nếu dùng trường Nửa ngày, nhập 1 cho nửa ngày hoặc 0 cho cả ngày. Kiểm tra thông tin trước khi lưu.'],
  ['Theo dõi trạng thái','Yêu cầu mới có trạng thái Chờ duyệt. Xem lại danh sách hoặc thông báo để biết kết quả.'],
 ],'Ngày nghỉ được tính theo lịch làm và ngày lễ của công ty. Yêu cầu trùng ngày hoặc không đủ phép có thể bị từ chối.',['duyet-yeu-cau','thong-bao'],'/leave-requests',{tags:['xin nghỉ','phép năm','nửa ngày','đơn nghỉ']}),
 make('duyet-yeu-cau','Duyệt hoặc từ chối đơn nghỉ','requests','Đối chiếu lịch làm và chính sách trước khi chấp thuận.',[
  ['Mở các yêu cầu đang chờ','Từ tổng quan chọn Đơn nghỉ phép hoặc mở mục này rồi lọc Chờ duyệt.'],
  ['Đọc Chi tiết','Kiểm tra người nghỉ, loại phép, khoảng ngày, nửa ngày và lý do.'],
  ['Duyệt hoặc từ chối','Chọn thao tác tương ứng. Hệ thống kiểm tra số dư và các ràng buộc liên quan.'],
  ['Kiểm tra cập nhật','Trạng thái được đổi và nhân viên nhận thông báo; đối chiếu bảng công khi tổng hợp kỳ đó.'],
 ],'Nếu loại phép chưa được cấu hình quyền lợi, nhờ người phụ trách chính sách kiểm tra thay vì sửa số dư tùy ý.',['xin-nghi-phep','tong-hop-cong'],'/leave-requests',{permission:'leave',tags:['phê duyệt','từ chối','quản lý đơn']}),
 make('tam-ung-luong','Đề nghị tạm ứng lương','requests','Gửi số tiền cần ứng và theo dõi việc phê duyệt.',[
  ['Mở Tạm ứng lương','Chọn Thêm mới hoặc Gửi yêu cầu trên Android.'],
  ['Nhập số tiền và ngày','Nhập số nguyên VND, ngày đề nghị dd/mm/yyyy và lý do.'],
  ['Gửi yêu cầu','Tài khoản nhân viên gửi cho chính mình. Kỳ lương sẽ được người có quyền lương kiểm tra và gán phù hợp.'],
  ['Xem kết quả','Theo dõi trạng thái; khoản tạm ứng chỉ được trừ khi đã duyệt và gán vào kỳ lương tương ứng.'],
 ],'Không điền mã kỳ lương nếu bạn chưa được người phụ trách xác nhận kỳ cần gán.',['thong-bao','xem-phieu-luong'],'/salary-advances',{audience:'employee',tags:['ứng lương','tạm ứng','tiền ứng']}),
 make('duyet-tam-ung','Duyệt tạm ứng và gán kỳ lương','payroll','Đảm bảo khoản ứng được tính đúng kỳ và không bị trừ hai lần.',[
  ['Mở Tạm ứng lương','Lọc các yêu cầu Chờ duyệt và mở Chi tiết.'],
  ['Kiểm tra số tiền, nhân viên và lý do','Đối chiếu đề nghị theo quy trình chi trả của công ty.'],
  ['Gán kỳ lương phù hợp','Sửa yêu cầu để chọn kỳ lương còn ở trạng thái nháp hoặc đã tính, rồi lưu.'],
  ['Duyệt và tính lại khi phù hợp','Duyệt khoản ứng trước khi tính hoặc tính lại bảng lương. Kiểm tra khoản ứng trong Chi tiết phiếu lương.'],
 ],'Khoản ứng chưa gán kỳ không được tự động trừ. Kỳ đã soát xét hoặc khóa không nhận thay đổi trực tiếp.',['lap-bang-luong','dieu-chinh-luong'],'/salary-advances',{permission:'payroll',tags:['duyệt tạm ứng','khấu trừ','kỳ lương']}),
 make('lap-bang-luong','Lập và tính bảng lương','payroll','Tính lương từ bảng công đã duyệt và chính sách của công ty.',[
  ['Chuẩn bị dữ liệu','Duyệt bảng công, kiểm tra phép, tạm ứng và khoản điều chỉnh. Tạo chính sách lương có hiệu lực phù hợp.'],
  ['Tạo kỳ lương','Vào Bảng lương → Thêm mới. Điền tên, ngày đầu/cuối và chính sách; chọn phạm vi chi nhánh/phòng ban nếu cần.'],
  ['Bấm Tính lương','Nếu còn công chưa tổng hợp hoặc chưa duyệt, xử lý trước rồi thực hiện lại.'],
  ['Xem Chi tiết và soát xét','Kiểm tra thu nhập, tạm ứng, khấu trừ và thực lĩnh. Có thể Tính lại trước bước soát xét.'],
 ],'Lương hiện là ước tính chưa hoàn tất nghĩa vụ thuế/bảo hiểm. Người phụ trách cần kiểm tra chính sách và đối chiếu trước khi sử dụng.',['tong-hop-cong','dieu-chinh-luong','khoa-phat-hanh'],'/payroll-periods',{permission:'payroll',minutes:5,tags:['tính lương','bảng lương','kỳ lương','chính sách']}),
 make('dieu-chinh-luong','Thêm thưởng, phụ cấp hoặc khấu trừ','payroll','Ghi nhận khoản thay đổi có lý do, tách khỏi dữ liệu chấm công.',[
  ['Mở Khoản điều chỉnh','Chọn Thêm mới và xác định nhân viên, kỳ lương cần áp dụng.'],
  ['Chọn loại và nhập tiền','Chọn thưởng, phụ cấp hoặc khấu trừ; nhập số nguyên VND và lý do.'],
  ['Kiểm tra và duyệt','Người có quyền lương kiểm tra khoản chờ rồi bấm Duyệt.'],
  ['Tính lại kỳ đang chỉnh sửa','Quay về bảng lương còn cho phép tính lại và xem khoản thay đổi trong Chi tiết.'],
 ],'Không sửa ngầm bảng lương đã khóa. Điều chỉnh cho kỳ đã khóa cần được xử lý có kiểm soát ở kỳ phù hợp sau đó.',['lap-bang-luong','duyet-tam-ung'],'/payroll-adjustments',{permission:'payroll',tags:['thưởng','phụ cấp','khấu trừ','điều chỉnh lương']}),
 make('khoa-phat-hanh','Duyệt, khóa và phát hành phiếu lương','payroll','Đi hết quy trình để nhân viên xem được phiếu lương của mình.',[
  ['Soát xét bảng đã tính','Sau khi kiểm tra Chi tiết, bấm Soát xét trên dòng kỳ lương.'],
  ['Duyệt lương','Xác nhận số liệu theo quy trình công ty. Thay đổi sau bước này bị hạn chế.'],
  ['Khóa kỳ lương','Bấm Khóa sau khi đã duyệt. Kỳ khóa không thể tính lại hoặc sửa trực tiếp.'],
  ['Phát hành phiếu lương','Bấm Phát hành. Nhân viên nhận thông báo và chỉ xem được phiếu của mình đã phát hành.'],
 ],'Thứ tự là Nháp → Đã tính → Đã soát xét → Đã duyệt → Đã khóa → Đã phát hành.',['lap-bang-luong','xem-phieu-luong'],'/payroll-periods',{permission:'payroll',illustration:'flow',tags:['khóa','phát hành','duyệt lương','phiếu lương']}),
 make('xem-phieu-luong','Xem phiếu lương của tôi','payroll','Hiểu thu nhập, tạm ứng và số tiền thực lĩnh ước tính.',[
  ['Mở Phiếu lương','Chọn mục Phiếu lương trên web hoặc trong menu Android.'],
  ['Chọn phiếu đã phát hành','Nhân viên chỉ thấy phiếu của mình đã được công ty phát hành.'],
  ['Đọc khoản thu và khoản trừ','Đối chiếu tổng thu nhập, tạm ứng, khấu trừ và thực lĩnh. Mở Chi tiết tính lương để xem các thành phần.'],
  ['Hỏi người phụ trách nếu có sai lệch','Nêu rõ kỳ lương và khoản cần kiểm tra. Không chia sẻ thông tin lương của mình qua kênh công cộng.'],
 ],'Nếu chưa thấy phiếu, hỏi quản lý xem kỳ đó đã phát hành chưa. Kết quả hiển thị là ước tính chưa hoàn tất thuế/bảo hiểm.',['thong-bao','tam-ung-luong'],'/payslips',{audience:'employee',tags:['lương của tôi','thực lĩnh','phiếu lương']}),
 make('xuat-bao-cao','Tra cứu và xuất báo cáo','reports','Chọn đúng phạm vi rồi tải Excel, PDF hoặc CSV.',[
  ['Mở Báo cáo','Chọn loại báo cáo như chấm công, bảng công tháng, đi trễ hoặc số dư phép.'],
  ['Đặt bộ lọc','Chọn ngày dd/mm/yyyy và nhân viên, phòng ban hoặc chi nhánh cần xem.'],
  ['Kiểm tra kết quả','Đối chiếu tên và khoảng ngày trước khi tải. Báo cáo lương cần quyền lương riêng.'],
  ['Chọn định dạng tải về','Bấm XLSX, PDF hoặc CSV. Với danh sách quá lớn, thu hẹp bộ lọc rồi xuất lại.'],
 ],'File xuất vẫn áp dụng phạm vi công ty và quyền truy cập. Ngày giờ trong file dùng định dạng Việt Nam.',['tong-hop-cong','lap-bang-luong'],'/reports',{permission:'reports',tags:['xuất','export','excel','pdf','csv','báo cáo']}),
 make('phan-quyen','Phân quyền và phạm vi chi nhánh','reports','Chỉ giao đúng quyền cần dùng cho từng người.',[
  ['Mở Cài đặt → Phân quyền','Chủ công ty mở danh sách thành viên và chọn Phân quyền.'],
  ['Chọn vai trò','Chọn quản trị, quản lý hoặc nhân viên phù hợp. Không đổi quyền chủ công ty tại đây.'],
  ['Chỉ định phạm vi và quyền bổ sung','Với quản lý, khai báo ID chi nhánh được giao. Quyền lương là quyền riêng, chỉ cấp khi cần; trường quyền bổ sung dùng danh sách JSON.'],
  ['Lưu và kiểm tra','Nhờ người được cấp quyền đăng nhập và kiểm tra các chức năng cần dùng.'],
 ],'Ví dụ quyền bổ sung ["payroll"] cho người được giao quản lý lương. Không cấp rộng chỉ để bỏ qua một lỗi truy cập.',['moi-tai-khoan','phong-ban-chi-nhanh'],'/memberships',{permission:'settings',roles:['owner'],minutes:4,tags:['vai trò','phân quyền','quản lý','chi nhánh']}),
 make('cau-hinh-cong-ty','Cài đặt ngày làm và chính sách công ty','reports','Kiểm tra các mặc định trước khi vận hành.',[
  ['Mở Cài đặt công ty','Kiểm tra tên công ty, ngày bắt đầu kỳ và ngày làm trong tuần.'],
  ['Cấu hình ngày làm','Danh sách ngày dùng 1 cho thứ Hai đến 7 cho Chủ nhật, ví dụ [1,2,3,4,5,6].'],
  ['Kiểm tra chính sách liên quan','Mở chính sách nghỉ phép, ngày lễ và chính sách lương theo quyền được giao.'],
  ['Lưu và thử với dữ liệu phù hợp','Lưu thay đổi rồi kiểm tra lại lịch và công. Không chỉnh chính sách đã dùng trong lương; tạo phiên bản mới khi cần.'],
 ],'Tất cả tính năng đã được mở, không cần mua gói hoặc gia hạn để sử dụng.',['phan-quyen','lap-bang-luong'],'/settings',{permission:'settings',tags:['cài đặt','ngày làm','chính sách','mặc định']}),
 make('quyen-rieng-tu','Yêu cầu truy cập hoặc xử lý dữ liệu cá nhân','reports','Theo dõi yêu cầu xuất dữ liệu hoặc ẩn danh theo quy trình công ty.',[
  ['Mở Yêu cầu dữ liệu','Tạo yêu cầu mới cho tài khoản của bạn.'],
  ['Chọn nhu cầu và nêu lý do','Chọn truy cập, xuất hoặc ẩn danh dữ liệu rồi gửi yêu cầu.'],
  ['Chờ người có quyền xem xét','Công ty xem xét phạm vi xử lý và các nghĩa vụ lưu trữ liên quan.'],
  ['Tải bản xuất khi đã duyệt','Chính người yêu cầu mới tải được bản xuất. Bản xuất hết hạn sau 7 ngày.'],
 ],'Ẩn danh không xóa các chứng từ công, lương hoặc nhật ký cần lưu giữ hợp lệ.',['thong-bao'],'/privacy-requests',{tags:['dữ liệu cá nhân','riêng tư','xuất dữ liệu','ẩn danh']}),
 make('cham-cong-android','Vào ca và ra ca trên Android','mobile','Chấm công đúng ca được phân bằng ứng dụng Mini HRM.',[
  ['Đăng nhập tài khoản đã được mời','Kiểm tra tên nhân viên, công ty và ngày giờ trên Trang chủ.'],
  ['Kiểm tra ca và địa điểm','Đọc giờ bắt đầu/kết thúc và chi nhánh trước khi bấm Vào ca.'],
  ['Chấm công theo chính sách','Bấm Vào ca khi bắt đầu, Ra ca khi kết thúc. Chỉ cấp vị trí khi chính sách GPS/Wi-Fi yêu cầu.'],
  ['Đọc kết quả','Đã xác minh nghĩa là đã ghi nhận hợp lệ; Chờ xác minh cần quản lý xem xét. Kiểm tra lịch sử để tránh gửi lặp.'],
 ],'Không có lịch hoặc ngoài khung giờ cho phép có thể bị từ chối. Hãy hỏi quản lý hoặc gửi điều chỉnh công thay vì chọn nhầm ca.',['cham-cong-offline','xem-lich-ca-nhan','dieu-chinh-cong'],'/schedules',{illustration:'mobile',tags:['android','điện thoại','gps','wifi','vào ca','ra ca']}),
 make('cham-cong-offline','Làm gì khi mất mạng hoặc chấm công bị từ chối?','mobile','Hiểu trạng thái chờ đồng bộ và chờ xác minh.',[
  ['Đọc thông báo sau khi bấm','Nếu không kết nối được máy chủ, ứng dụng lưu yêu cầu trên thiết bị để chờ đồng bộ.'],
  ['Giữ ứng dụng và kết nối lại','Mở lại mạng. Android sẽ thử đồng bộ qua tác vụ nền; có thể cần chờ nếu hệ điều hành đang tiết kiệm pin.'],
  ['Kiểm tra trạng thái sau đồng bộ','Đã đồng bộ · Chờ xác minh không có nghĩa là đã duyệt công. Quản lý cần đối chiếu thời gian và bằng chứng.'],
  ['Gửi điều chỉnh nếu cần','Nếu vị trí ngoài bán kính, Android không đọc được Wi-Fi hoặc thiếu giờ, gửi yêu cầu điều chỉnh có lý do.'],
 ],'Đồng bộ yêu cầu trước khi đăng xuất: đăng xuất sẽ xóa hàng đợi trên thiết bị. GPS và Wi-Fi không bảo đảm loại bỏ hoàn toàn gian lận.',['cham-cong-android','dieu-chinh-cong'],'/attendance-corrections',{illustration:'flow',minutes:4,tags:['offline','mất mạng','đồng bộ','từ chối','vị trí','quên giờ ra']}),
 make('quan-tri-cong-ty','Quản lý công ty trên nền tảng','platform','Tạo, kiểm tra và quản lý trạng thái công ty.',[
  ['Mở Công ty','Vào danh sách công ty trong cổng quản trị hệ thống.'],
  ['Tạo công ty khi được yêu cầu','Chọn Thêm cấu hình, nhập tên công ty, email chủ công ty, loại hình, múi giờ và chi nhánh đầu tiên.'],
  ['Kiểm tra Chi tiết','Xem thông tin công ty và số nhân viên. Mỗi công ty có dữ liệu nghiệp vụ tách biệt.'],
  ['Tạm ngưng hoặc kích hoạt có trách nhiệm','Dùng thao tác trên đúng dòng công ty. Thay đổi được ghi nhật ký; quản trị nền tảng không tự truy cập nghiệp vụ nhân sự công ty.'],
 ],'Không có gói thuê bao hoặc giới hạn nhân viên. Tạm ngưng công ty là thao tác quản trị riêng, không phải hết hạn gói.',['kiem-tra-he-thong'],'/super/tenants',{audience:'platform',tags:['super admin','công ty','tạm ngưng','nền tảng']}),
 make('kiem-tra-he-thong','Theo dõi sức khỏe và nhật ký hệ thống','platform','Biết nơi kiểm tra hàng đợi, dịch vụ và thao tác quản trị.',[
  ['Mở Sức khỏe hệ thống','Đối chiếu kết nối dữ liệu, số tác vụ đang chờ và tác vụ thất bại.'],
  ['Kiểm tra cấu hình tích hợp','Trạng thái đã cấu hình không đồng nghĩa dịch vụ đã gửi thành công; cần kiểm tra môi trường thực tế.'],
  ['Mở Nhật ký hoạt động','Tra thời điểm, người thực hiện và nội dung thao tác nền tảng khi đối chiếu sự cố.'],
  ['Xử lý theo quy trình vận hành','Nhờ người vận hành kiểm tra cấu hình và thử lại tác vụ phù hợp. Không sửa lịch sử công hoặc lương để giải quyết lỗi gửi thư.'],
 ],'Quản trị hệ thống là không gian riêng; không dùng tài khoản này thay chủ công ty để duyệt công hoặc lương.',['quan-tri-cong-ty'],'/super/health',{audience:'platform',tags:['sức khỏe','hàng đợi','nhật ký','lỗi gửi thư']}),
];

export function audienceFromSession(session:any):Audience{let permissions:string[]=[];try{permissions=JSON.parse(session.membership?.permissions||'[]');}catch{}return {userId:Number(session.user.id),tenantId:session.tenant?Number(session.tenant.id):null,role:session.membership?.role||'platform',superUser:!!Number(session.user.is_super),permissions};}
export function can(a:Audience,permission:string):boolean{if(a.superUser)return false;if(a.role==='owner'||a.permissions.includes(permission))return true;const defaults:Record<string,string[]>={admin:['people','schedule','attendance','leave','reports','settings','invite'],manager:['schedule','attendance','leave','reports'],employee:['self']};return (defaults[a.role]||[]).includes(permission);}
export function canRead(article:Article,a:Audience):boolean{if(article.audience==='platform')return a.superUser;if(a.superUser)return article.audience==='all';if(article.audience==='employee'&&a.role!=='employee')return false;return (!article.permission||can(a,article.permission))&&(!article.roles||article.roles.includes(a.role));}
export function normalizeSearch(value:string):string{return value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/đ/g,'d').replace(/Đ/g,'D').toLowerCase().trim();}
export function searchArticles(query:string,a:Audience,topic?:string):Article[]{const terms=normalizeSearch(query).split(/\s+/).filter(Boolean);return articles.filter(article=>canRead(article,a)&&(!topic||article.topic===topic)).map(article=>{const title=normalizeSearch(article.title);const text=normalizeSearch([article.title,article.summary,...article.tags,...article.steps.map(s=>s.title+' '+s.body)].join(' '));return {article,match:terms.every(t=>text.includes(t)),rank:terms.reduce((n,t)=>n+(title.includes(t)?3:0)+article.tags.filter(tag=>normalizeSearch(tag).includes(t)).length,0)};}).filter(r=>r.match).sort((a,b)=>b.rank-a.rank).map(r=>r.article);}
export function actionPath(article:Article,a:Audience):string{return article.action.path==='home'?(a.superUser?'/super':'/'):article.action.path;}
export function storageKey(a:Audience):string{return `minihrm:guide:v${GUIDE_VERSION}:${a.userId}:${a.tenantId??'platform'}:${a.role}`;}

export type TourStep={id:string;title:string;body:string;route:string;target:string;navigation?:boolean;note?:string};
const step=(id:string,title:string,body:string,route:string,target:string,navigation=false,note?:string):TourStep=>({id,title,body,route,target:`[data-tour="${target}"]`,navigation,note});
export function tourSteps(a:Audience):TourStep[]{
 const home=a.superUser?'/super':'/';const steps=[step('overview','Bắt đầu từ tổng quan',a.superUser?'Theo dõi công ty, người dùng và hoạt động của nền tảng.':'Xem nhanh tình hình chấm công và các yêu cầu cần xử lý trong phạm vi của bạn.',home,'overview'),step('navigation','Các chức năng được chia theo nhóm','Menu giúp bạn chuyển giữa các công việc. Trên màn hình nhỏ, dùng nút ba gạch để mở menu.',home,'navigation',true)];
 if(a.superUser){steps.push(step('companies','Quản lý công ty','Tạo công ty và kiểm tra trạng thái tại đây. Mọi thao tác quản trị đều được ghi nhật ký.','/super/tenants','platform-create'),step('health','Theo dõi hệ thống','Kiểm tra kết nối và hàng đợi thông báo khi cần hỗ trợ vận hành.','/super/health','platform-content'));}
 else{
  if(can(a,'people'))steps.push(step('employees','Thêm hồ sơ nhân viên','Bấm Thêm mới sau khi kết thúc tour để tạo hồ sơ. Chuẩn bị mã nhân viên, họ tên, email và ngày vào làm.','/employees','add-record'));
  if(can(a,'people')||a.role==='employee')steps.push(step('filters','Tìm đúng dữ liệu','Dùng tìm kiếm và khoảng ngày để thu hẹp danh sách. Ngày nhập theo dd/mm/yyyy.',a.role==='employee'?'/timesheets':'/employees','filters'));
  if(can(a,'schedule')||a.role==='employee')steps.push(step('schedule','Kiểm tra lịch làm việc',a.role==='employee'?'Xem lịch được phân cho bạn; hỏi quản lý nếu chưa thấy ca.':'Tạo ca trước, sau đó chọn người, chi nhánh và ngày để xếp lịch.','/schedules','filters'));
  if(can(a,'attendance'))steps.push(step('timesheets','Kiểm tra công trước khi duyệt','Chọn khoảng ngày và tổng hợp công, xử lý các dòng thiếu giờ hoặc chờ xác minh rồi mới duyệt.','/timesheets','timesheet-actions'));
  if(a.role==='employee')steps.push(step('requests','Gửi yêu cầu cho quản lý','Tạo đơn nghỉ phép cho chính mình, nhập ngày và lý do rồi theo dõi kết quả.','/leave-requests','add-record'));
  if(can(a,'payroll'))steps.push(step('payroll','Đi theo quy trình bảng lương','Chỉ tính từ công đã duyệt. Soát xét, duyệt, khóa rồi phát hành để nhân viên xem phiếu.','/payroll-periods','payroll-workflow',false,'Kết quả là ước tính chưa hoàn tất nghĩa vụ thuế và bảo hiểm.'));
  if(a.role==='employee')steps.push(step('payslips','Xem phiếu lương của mình','Phiếu chỉ xuất hiện sau khi công ty phát hành. Bạn không xem được phiếu của đồng nghiệp.','/payslips','filters'));
 }
 steps.push(step('help','Trợ giúp luôn ở đây','Tìm theo từ khóa hoặc chủ đề, mở bài có hình minh họa và xem lại tour bất cứ lúc nào.','/help','help-search'));return steps;
}
