import {spawn,spawnSync} from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import assert from 'node:assert/strict';
const root=process.cwd();const php=process.env.PHP_BINARY||path.join(root,'.tools/php/php.exe');
fs.mkdirSync(path.join(root,'.tools'),{recursive:true});
const dbName='integration-'+Date.now()+'.sqlite';
const env={...process.env,CI_ENVIRONMENT:'development',HRM_SQLITE_PATH:dbName,'HRM_WEB_URL':'http://localhost:5173'};
for(const args of [['backend/spark','migrate'],['backend/spark','db:seed','DemoSeeder']]){const r=spawnSync(php,args,{cwd:root,env,encoding:'utf8'});if(r.status!==0||/Exception|Error\]/.test(r.stdout)){console.error(r.stdout,r.stderr);process.exit(1);}}
const port=18081+Math.floor(Math.random()*1000);const server=spawn(php,['-S',`127.0.0.1:${port}`,'-t','backend/public','backend/vendor/codeigniter4/framework/system/rewrite.php'],{cwd:root,env,stdio:'ignore'});
let checks=0;let owner,other,employee,superUser;
async function req(route,token,method='GET',body){const r=await fetch(`http://127.0.0.1:${port}/api/v1/${route}`,{method,headers:{'Content-Type':'application/json',...(token?{Authorization:'Bearer '+token}:{})},...(body===undefined?{}:{body:JSON.stringify(body)})});const type=r.headers.get('content-type')||'';return {status:r.status,data:type.includes('json')?await r.json():Buffer.from(await r.arrayBuffer()),headers:r.headers};}
function ok(condition,message){assert.ok(condition,message);checks++;}
async function success(route,token,method='GET',body){const r=await req(route,token,method,body);ok(r.status<300,`${route}: ${r.status} ${JSON.stringify(r.data).slice(0,200)}`);return r.data;}
async function login(email){return (await success('auth/login',null,'POST',{email,password:'MiniHrm!2026Demo'})).access_token;}
try {
 for(let i=0;i<50;i++){try{await fetch(`http://127.0.0.1:${port}/api/v1/health`);break;}catch{await new Promise(r=>setTimeout(r,100));}}
 owner=await login('owner@minihrm.test');other=await login('other@minihrm.test');employee=await login('employee@minihrm.test');superUser=await login('super@minihrm.test');
 const registeredEmail='registered-'+Date.now()+'@company.test';await success('auth/register',null,'POST',{full_name:'Chủ công ty đăng ký mới',email:registeredEmail,password:'MiniHrm!2026Demo'});
 ok((await req('auth/login',null,'POST',{email:registeredEmail,password:'MiniHrm!2026Demo'})).status===403,'Unverified owner cannot log in');
 const mailDir=path.join(root,'backend/writable/mail');const verificationFile=fs.readdirSync(mailDir).map(name=>path.join(mailDir,name)).find(file=>{try{return JSON.parse(fs.readFileSync(file,'utf8')).to===registeredEmail;}catch{return false;}});ok(!!verificationFile,'Verification email is safely spooled without credentials');
 const verifyToken=new URL(JSON.parse(fs.readFileSync(verificationFile,'utf8')).body.split(' ').pop()).searchParams.get('token');await success('auth/verify',null,'POST',{token:verifyToken});ok((await req('auth/verify',null,'POST',{token:verifyToken})).status===422,'Verification token is single use');
 const registered=await login(registeredEmail);ok((await success('auth/me',registered)).tenant===null,'Verified owner starts without a tenant');const onboarded=await success('onboarding',registered,'POST',{name:'Công ty đăng ký qua API',business_type:'office',timezone:'Asia/Ho_Chi_Minh'});ok(onboarded.tenant.name==='Công ty đăng ký qua API','Registration and onboarding persist a company');fs.unlinkSync(verificationFile);
 const mine=(await success('employees',owner)).data;const theirs=(await success('employees',other)).data;
 for(const id of theirs.map(e=>e.id)) {ok((await req('employees/'+id,owner)).status===404,'Cross tenant employee GET');ok((await req('employees/'+id,owner,'PATCH',{full_name:'Attack'})).status===404,'Cross tenant employee PATCH');}
 const crossBranch=(await success('branches',other)).data[0].id;
 ok((await req('employees',owner,'POST',{code:'ATTACK',full_name:'Attack',email:'attack@test.local',hire_date:'2026-01-01',branch_id:crossBranch})).status===404,'Cross tenant branch reference');
 for(const r of ['attendance','timesheets','schedules']){const foreign=(await success(r,other)).data[0];ok((await req(r+'/'+foreign.id,owner)).status===404,'Cross tenant '+r);}
 ok((await req('branches/'+crossBranch,owner)).status===404,'Cross tenant branch');
 ok((await req('super/dashboard',owner)).status===403,'Owner cannot use platform portal');ok((await req('employees',superUser)).status===403,'Super admin cannot use company API');
 const own=(await success('employees',employee)).data;ok(own.length===1,'Employee sees only own profile');ok(!('base_salary' in own[0]),'Employee profile redacts pay policy');
 const mobile=await success('mobile-home',employee);ok(mobile.schedules.length>0,'Mobile home has real schedule');ok(!('base_salary' in mobile.employee),'Mobile home redacts sensitive pay');
 const ep=own[0].id;ok((await req('employees/'+mine.find(e=>e.id!==ep).id,employee)).status===404,'Employee cannot view coworker');
 ok((await req('employees/'+ep+'/history',employee)).status===403,'Employee cannot read personnel audit');ok((await success('employees/'+ep+'/history',owner)).data.length>0,'Authorized personnel history');
 ok((await req('reports/payroll-summary',employee)).status===403,'Employee report denied');ok((await req('exports/employees?format=xlsx',employee)).status===403,'Employee export denied');
 const report=await success('reports/daily-attendance?employee_id='+theirs[0].id,owner);ok(report.data.length===0,'Report foreign employee filter cannot leak rows');
 const newEmployee=await success('employees',owner,'POST',{tenant_id:999,code:'NEW1',full_name:'Người kiểm thử',email:'new1@test.local',hire_date:'2026-01-01',employment_type:'full_time'});ok(newEmployee.tenant_id===mine[0].tenant_id,'Client tenant ignored');
 const correction=await success('attendance-corrections',employee,'POST',{employee_id:theirs[0].id,timesheet_id:(await success('timesheets',employee)).data[0].id,new_values:{check_in:'2026-10-27T01:00:00Z',check_out:'2026-10-27T10:00:00Z'},reason:'Quên chấm công'});ok(correction.employee_id===ep,'Employee id derived from membership');
 const offlinePayload={schedule_id:mobile.schedules[0].id,kind:'check_in',offline:1,local_timestamp:'2026-10-27T01:00:00Z',idempotency_key:'offline-integration-123456'};
 const offline=await success('attendance/attempt',employee,'POST',offlinePayload);ok(offline.status==='pending_verification','Offline cannot self-verify');const duplicate=await success('attendance/attempt',employee,'POST',offlinePayload);ok(duplicate.id===offline.id,'Attendance retries idempotent');
 ok((await req('attendance/attempt',employee,'POST',{...offlinePayload,kind:'check_out'})).status===409,'Reused key rejects changed payload');
 await success('attendance/'+offline.id+'/reject',owner,'POST',{});
 const types=(await success('leave-types',employee)).data;
 const leave=await success('leave-requests',employee,'POST',{employee_id:theirs[0].id,leave_type_id:types.find(t=>!t.paid).id,start_date:'2026-11-03',end_date:'2026-11-03',reason:'Việc riêng',half_day:1});ok(Number(leave.days)===0.5,'Half-day leave');await success('leave-requests/'+leave.id+'/approve',owner,'POST',{});
 ok((await req('leave-requests/'+leave.id+'/approve',owner,'POST',{})).status===409,'Approval not repeatable');
 const otherPolicy=(await success('salary-policies',other)).data[0];
 const foreignPeriod=await success('payroll-periods',other,'POST',{name:'Cross tenant payroll',start_date:'2026-12-01',end_date:'2026-12-31',policy_id:otherPolicy.id});
 ok((await req('payroll-periods/'+foreignPeriod.id,owner)).status===404,'Cross tenant payroll period');
 await success('payroll-periods/'+foreignPeriod.id+'/calculate',other,'POST',{});
 const foreignSlip=(await success('payslips',other)).data[0];ok((await req('payslips/'+foreignSlip.id,owner)).status===404,'Cross tenant payslip');
 const file=await success('files/upload',other,'POST',{employee_id:theirs[0].id,name:'private.pdf',content:Buffer.from('%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF').toString('base64')});ok((await req('files/'+file.id+'/download',owner)).status===404,'Cross tenant private file download');
 const png='iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZlD8AAAAASUVORK5CYII=';
 const avatar=await success('employees/'+ep+'/avatar',owner,'POST',{name:'avatar.png',content:png});ok(Number(avatar.avatar_file_id)>0,'Avatar persisted as a protected image');ok((await req('files/'+avatar.avatar_file_id+'/download',employee)).status===200,'Employee can view own avatar');ok((await req('files/'+avatar.avatar_file_id+'/download',other)).status===404,'Avatar cannot cross tenants');ok((await req('employees/'+ep+'/avatar',employee,'POST',{name:'avatar.png',content:png})).status===403,'Avatar personnel mutation requires permission');
 const sheets=(await success('timesheets?limit=200',owner)).data;
 for(const sheet of sheets){if(sheet.review_status==='pending'&&sheet.status!=='pending_verification'&&sheet.status!=='missing_check_out')await success('timesheets/'+sheet.id+'/approve',owner,'POST',{});}
 const policy=(await success('salary-policies',owner)).data[0];const period=await success('payroll-periods',owner,'POST',{name:'October verified',start_date:'2026-10-01',end_date:'2026-10-31',policy_id:policy.id});
 await success('payroll-periods/'+period.id+'/calculate',owner,'POST',{});let slips=(await success('payslips?payroll_period_id='+period.id,owner)).data;ok(slips.length===5,'Payroll contains real employees');ok(JSON.parse(slips[0].breakdown).compliance==='non_statutory_estimate','Payroll compliance label');
 ok((await success('payslips',employee)).data.length===0,'Unpublished slips hidden from employee');
 for(const action of ['review','approve','lock','publish'])await success('payroll-periods/'+period.id+'/'+action,owner,'POST',{});
 const ownSlips=(await success('payslips',employee)).data;ok(ownSlips.length===1&&ownSlips[0].employee_id===ep,'Published employee payslip only own');
 ok((await req('payroll-periods/'+period.id+'/calculate',owner,'POST',{})).status===409,'Locked payroll blocks recalculation');ok((await req('payroll-adjustments',owner,'POST',{employee_id:ep,payroll_period_id:period.id,kind:'bonus',amount:10000,reason:'Attack'})).status===409,'Locked payroll blocks adjustments');
 for(const format of ['xlsx','pdf','csv']) {const out=await req('exports/employees?format='+format,owner);ok(out.status===200&&out.data.length>100,'Real '+format+' export');if(format==='csv'){const csv=out.data.toString('utf8');ok(csv.includes('01/01/2026')&&!csv.includes('2026-01-01'),'Export dates use dd/mm/yyyy');ok(/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}/.test(csv),'Export timestamps use Vietnam format');}}
 ok((await req('billing/renew',owner,'POST',{})).status===410,'No renewal or payment required for full features');
 const fullAccess=await success('subscriptions',owner);ok(fullAccess.read_only===false&&fullAccess.employee_limit===null,'All accounts have unlimited full access');
 const noPlanBranch=await success('branches',owner,'POST',{name:'Chi nhánh không cần nâng cấp',attendance_policy:'gps',latitude:10.77,longitude:106.69,radius:100});ok(!!noPlanBranch.id&&noPlanBranch.attendance_policy==='gps','GPS and multiple branches do not require a plan');
 await success('reports/department-payroll',owner);
 const privacy=await success('privacy-requests',employee,'POST',{kind:'export',reason:'Tải dữ liệu cá nhân'});await success('privacy-requests/'+privacy.id+'/approve',owner,'POST',{});const exported=await success('privacy-requests/'+privacy.id+'/download',employee);ok(exported.employees.length===1&&exported.employees[0].id===ep,'Privacy export contains only subject');ok((await req('privacy-requests/'+privacy.id+'/download',owner)).status===403,'Owner cannot download another subject export');ok((await req('privacy-requests/'+privacy.id,other)).status===404,'Cross-tenant privacy request');
 const importPath=path.join(root,'.tools','integration-import.xlsx');const fixture=spawnSync(php,['tests/create-import.php',importPath],{cwd:root,encoding:'utf8'});ok(fixture.status===0,'Generate real XLSX import fixture');const imported=await success('imports',owner,'POST',{content:fs.readFileSync(importPath).toString('base64')});ok(imported.imported===2,'Transactional XLSX import');ok((await req('imports',owner,'POST',{content:fs.readFileSync(importPath).toString('base64')})).status===409,'Duplicate import conflicts');ok((await success('employees?limit=200',owner)).data.length===7,'Duplicate import rolls back');fs.unlinkSync(importPath);
 await success('departments',owner,'POST',{name:'Full access without subscription'});ok((await success('employees',owner)).data.length===7,'Full access preserves tenant data');
 const token=await success('auth/login',null,'POST',{email:'owner@minihrm.test',password:'MiniHrm!2026Demo'});const rotated=await success('auth/refresh',null,'POST',{refresh_token:token.refresh_token});ok((await req('auth/refresh',null,'POST',{refresh_token:token.refresh_token})).status===401,'Refresh token cannot replay');await success('auth/logout',rotated.access_token,'POST',{});ok((await req('auth/me',rotated.access_token)).status===401,'Logout revokes token');
 const database=process.env.HRM_TEST_MYSQL_DB?'mysql':'sqlite';fs.mkdirSync('tests/results',{recursive:true});fs.writeFileSync('tests/results/api-'+database+'.json',JSON.stringify({checks,database,status:'passed',completed_at:new Date().toISOString()},null,2));console.log(`API integration (${database}): ${checks} checks passed`);
} finally {server.kill();setTimeout(()=>{try{fs.unlinkSync(path.join(root,'backend/writable',dbName));}catch{}},400);}
