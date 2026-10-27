import {useId,useState} from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import {Expand,X} from 'lucide-react';
import type {Article} from './catalog';

const sample:Record<string,{heading:string;columns:string[];rows:string[][];button:string}>={
 start:{heading:'Tổng quan',columns:['Đội ngũ','Chấm công','Yêu cầu'],rows:[['Nhân viên','Đã vào ca','Chờ duyệt'],['Theo dõi','Kiểm tra','Xử lý']],button:'Mở chức năng'},
 people:{heading:'Nhân viên',columns:['Họ và tên','Mã nhân viên','Thao tác'],rows:[['Nguyễn An','NV001','Chi tiết · Mời'],['Trần Minh','NV002','Chi tiết · Sửa']],button:'＋ Thêm mới'},
 time:{heading:'Bảng công & lịch làm',columns:['Ngày làm','Giờ vào / ra','Duyệt công'],rows:[['27/10/2026','08:00:00 – 17:00:00','Chờ duyệt'],['28/10/2026','08:00:00 – 17:00:00','Đã duyệt']],button:'Tổng hợp công'},
 requests:{heading:'Đơn nghỉ phép',columns:['Nhân viên','Ngày nghỉ','Trạng thái'],rows:[['Nguyễn An','27/10/2026','Chờ duyệt'],['Trần Minh','28/10/2026','Đã duyệt']],button:'＋ Thêm mới'},
 payroll:{heading:'Bảng lương',columns:['Kỳ lương','Thực lĩnh ước tính','Trạng thái'],rows:[['Tháng 10/2026','Xem Chi tiết','Đã tính'],['Nháp → Soát xét','Duyệt → Khóa','Phát hành']],button:'＋ Thêm mới'},
 reports:{heading:'Báo cáo & cài đặt',columns:['Phạm vi','Khoảng ngày','Kết quả'],rows:[['Công ty của bạn','01/10/2026','Kiểm tra dữ liệu'],['Theo quyền truy cập','31/10/2026','Xuất file']],button:'↓ Xuất Excel'},
 mobile:{heading:'Chấm công',columns:['Ngày làm','Ca được phân','Trạng thái'],rows:[['27/10/2026','08:00:00 – 17:00:00','Vào ca'],['Yêu cầu offline','Đã đồng bộ','Chờ xác minh']],button:'Xem lịch sử'},
 platform:{heading:'Quản trị công ty',columns:['Công ty','Trạng thái','Thao tác'],rows:[['Công ty mẫu A','Hoạt động','Chi tiết'],['Công ty mẫu B','Hoạt động','Tạm ngưng']],button:'＋ Thêm cấu hình'},
};
function lines(text:string,max=30):string[]{const output:string[]=[];for(const word of text.split(' ')){if(!output.length||output[output.length-1].length+word.length+1>max)output.push(word);else output[output.length-1]+=' '+word;}return output;}
export function Diagram({article}:{article:Article}){
 const titleId=useId();const data=sample[article.topic];
 return <svg viewBox="0 0 900 440" role="img" aria-labelledby={titleId} className="guide-diagram">
  <title id={titleId}>Minh họa {article.title}: {article.steps.map((s,i)=>`${i+1}. ${s.title}`).join('; ')}</title>
  <rect width="900" height="440" rx="18" fill="#eef4ff"/>
  <circle cx="842" cy="38" r="95" fill="#e4edff"/>
  <rect x="28" y="26" width="844" height="286" rx="12" fill="white" stroke="#dbe5f3"/>
  <path d="M40 26H161V312H40Q28 312 28 300V38Q28 26 40 26" fill="#14233f"/>
  <text x="48" y="60" fill="white" fontSize="18" fontWeight="700">mini hrm</text>
  {['Tổng quan','Nhân sự','Lịch & công','Yêu cầu','Bảng lương','Hướng dẫn'].map((label,i)=><g key={label}><rect x="41" y={82+i*32} width="107" height="25" rx="5" fill={i===5?'#2563eb':'transparent'}/><text x="51" y={99+i*32} fill={i===5?'white':'#a7b9d7'} fontSize="10">{label}</text></g>)}
  <text x="185" y="65" fill="#213653" fontSize="20" fontWeight="700">{data.heading}</text>
  <rect x="686" y="43" width="161" height="32" rx="6" fill="#2563eb"/><text x="766" y="64" fill="white" textAnchor="middle" fontSize="11" fontWeight="600">{data.button}</text>
  <rect x="184" y="89" width="329" height="32" rx="6" fill="#f8fafc" stroke="#e1e8f2"/><text x="198" y="110" fill="#8191a9" fontSize="11">Tìm kiếm hoặc chọn khoảng ngày…</text>
  <rect x="531" y="89" width="154" height="32" rx="6" fill="white" stroke="#e1e8f2"/><text x="546" y="110" fill="#6a7e9b" fontSize="11">27/10/2026</text>
  <rect x="184" y="139" width="663" height="35" rx="4" fill="#f4f7fb"/>
  {data.columns.map((col,i)=><text key={col} x={201+i*220} y="161" fill="#71839f" fontSize="10">{col}</text>)}
  {data.rows.map((row,j)=><g key={j}><path d={`M184 ${222+j*54}H847`} stroke="#edf1f6"/>{row.map((v,i)=><text key={i} x={201+i*220} y={204+j*54} fill={i===2?'#2563eb':'#405879'} fontSize="10">{v}</text>)}</g>)}
  <rect x="184" y="279" width="663" height="16" rx="4" fill="#f8fafc"/>
  <text x="201" y="290" fill="#8c9ab0" fontSize="8">Dữ liệu minh họa · Thao tác thực tế tùy vai trò và màn hình</text>
  {article.steps.map((step,i)=><g key={step.title} transform={`translate(${29+i*218},333)`}>
   <rect width="190" height="84" rx="10" fill="white" stroke="#d5e2f6"/>
   <circle cx="23" cy="23" r="12" fill="#e9f0ff"/><text x="23" y="27" textAnchor="middle" fill="#2563eb" fontSize="11" fontWeight="700">{i+1}</text>
   {lines(step.title,23).slice(0,3).map((line,j)=><text key={j} x="15" y={49+j*13} fill="#344f73" fontSize="10" fontWeight="500">{line}</text>)}
   {i<article.steps.length-1&&<path d="M196 40h12m-4-4 4 4-4 4" fill="none" stroke="#9cb4d8" strokeWidth="2"/>}
  </g>)}
 </svg>;
}
export function GuideFigure({article}:{article:Article}){
 const [open,setOpen]=useState(false);const mobile=article.illustration==='mobile';
 const picture=()=>mobile?<img src="/help/android-attendance.png" alt="Ứng dụng Android Mini HRM: ngày giờ Việt Nam, ca được phân và nút Vào ca, Ra ca. Dữ liệu mẫu." loading="lazy" className="guide-mobile-image"/>:<Diagram article={article}/>;
 return <figure className={'guide-figure '+(mobile?'guide-figure-mobile':'')}><button className="guide-figure-expand" onClick={()=>setOpen(true)} aria-label="Phóng to hình minh họa">{picture()}<span><Expand size={14}/> Phóng to</span></button><figcaption>{mobile?'Ảnh màn hình Android với dữ liệu mẫu.':'Hình minh họa nhóm chức năng và trình tự thực hiện; không phải dữ liệu của bạn.'}</figcaption>
  <Dialog.Root open={open} onOpenChange={setOpen}><Dialog.Portal><Dialog.Overlay className="dialog-overlay"/><Dialog.Content className="dialog guide-image-dialog"><div className="dialog-heading"><Dialog.Title>Minh họa: {article.title}</Dialog.Title><Dialog.Close aria-label="Đóng hình minh họa"><X size={20}/></Dialog.Close></div><Dialog.Description className="muted">Đối chiếu với các bước bên dưới bài hướng dẫn.</Dialog.Description><div className="guide-image-scroll" tabIndex={0}>{picture()}</div></Dialog.Content></Dialog.Portal></Dialog.Root>
 </figure>;
}
