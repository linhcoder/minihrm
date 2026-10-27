export type Row = Record<string, any>;
let access = sessionStorage.getItem('hrm.access') || '';
let refresh = sessionStorage.getItem('hrm.refresh') || '';
let refreshing: Promise<void> | null = null;
export function saveTokens(v:Row) {access=v.access_token;refresh=v.refresh_token;sessionStorage.setItem('hrm.access',access);sessionStorage.setItem('hrm.refresh',refresh);}
export function clearTokens(){access='';refresh='';sessionStorage.removeItem('hrm.access');sessionStorage.removeItem('hrm.refresh');}
export async function api(path:string, method='GET', body?:unknown, retry=true):Promise<any> {
  const r=await fetch('/api/v1/'+path,{method,headers:{'Content-Type':'application/json',...(access?{Authorization:'Bearer '+access}:{})},...(body===undefined?{}:{body:JSON.stringify(body)})});
  if(r.status===401&&refresh&&retry&&(!path.startsWith('auth/')||path==='auth/me')) {
    refreshing??=fetch('/api/v1/auth/refresh',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({refresh_token:refresh})}).then(async res=>{if(!res.ok){clearTokens();throw Error('Phiên đăng nhập hết hạn.');}saveTokens(await res.json());}).finally(()=>{refreshing=null;});
    await refreshing;return api(path,method,body,false);
  }
  const data=await r.json();if(!r.ok)throw Error(data.error?.message+(Object.keys(data.error?.details||{}).length?' '+Object.entries(data.error.details).map(([k,v])=>`${k}: ${v}`).join('; '):''));return data;
}
export async function download(path:string,format:string) {
  // Refresh through the regular API before obtaining a binary export.
  await api('auth/me');
  const r=await fetch('/api/v1/'+path+(path.includes('?')?'&':'?')+'format='+format,{headers:{Authorization:'Bearer '+access}});
  if(!r.ok){const j=await r.json();throw Error(j.error?.message||'Không thể tải tệp.');}
  const url=URL.createObjectURL(await r.blob());const a=document.createElement('a');a.href=url;a.download='minihrm.'+format;a.click();URL.revokeObjectURL(url);
}
export async function privateImage(id:number):Promise<string>{await api('auth/me');const r=await fetch('/api/v1/files/'+id+'/download',{headers:{Authorization:'Bearer '+access}});if(!r.ok)throw Error('Không tải được ảnh.');return URL.createObjectURL(await r.blob());}
