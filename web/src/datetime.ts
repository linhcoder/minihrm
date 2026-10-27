export const VIETNAM_TIMEZONE='Asia/Ho_Chi_Minh';
export function formatDateTime(value:unknown):unknown {
 if(typeof value!=='string')return value;
 const date=value.match(/^(\d{4})-(\d{2})-(\d{2})$/);if(date)return `${date[3]}/${date[2]}/${date[1]}`;
 if(/^\d{2}:\d{2}$/.test(value))return value+':00';
 if(!/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/.test(value))return value;
 const instant=new Date(/(?:Z|[+-]\d{2}:\d{2})$/.test(value)?value:value.replace(' ','T')+'Z');if(Number.isNaN(instant.getTime()))return value;
 const parts=new Intl.DateTimeFormat('vi-VN',{timeZone:VIETNAM_TIMEZONE,day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit',hourCycle:'h23'}).formatToParts(instant);
 const p=(key:string)=>parts.find(p=>p.type===key)?.value||'';return `${p('day')}/${p('month')}/${p('year')} ${p('hour')}:${p('minute')}:${p('second')}`;
}
export function toIsoDate(value:string):string {
 const m=value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);if(!m)return value;
 const iso=`${m[3]}-${m[2]}-${m[1]}`;const d=new Date(iso+'T00:00:00Z');return !Number.isNaN(d.getTime())&&d.toISOString().slice(0,10)===iso?iso:value;
}
export function formatDateTree(value:any):any {if(Array.isArray(value))return value.map(formatDateTree);if(value&&typeof value==='object')return Object.fromEntries(Object.entries(value).map(([k,v])=>[k,formatDateTree(v)]));return formatDateTime(value);}
export function vietnamToday():string {const p=new Intl.DateTimeFormat('en-CA',{timeZone:VIETNAM_TIMEZONE,year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());return ['year','month','day'].map(k=>p.find(p=>p.type===k)?.value).join('-');}
