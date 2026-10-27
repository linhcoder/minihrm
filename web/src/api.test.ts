import {beforeEach,afterEach,describe,it,expect,vi} from 'vitest';
const storage=new Map<string,string>();
beforeEach(()=>{vi.resetModules();storage.clear();vi.stubGlobal('sessionStorage',{getItem:(k:string)=>storage.get(k)??null,setItem:(k:string,v:string)=>storage.set(k,v),removeItem:(k:string)=>storage.delete(k)});});
afterEach(()=>{vi.unstubAllGlobals();});
const response=(body:unknown,status=200)=>new Response(JSON.stringify(body),{status,headers:{'Content-Type':'application/json'}});
describe('authenticated API client',()=>{
 it('rotates an expired token when restoring the session through auth/me',async()=>{
  storage.set('hrm.access','expired');storage.set('hrm.refresh','refresh-once');
  const fetcher=vi.fn().mockResolvedValueOnce(response({error:{message:'Expired'}},401)).mockResolvedValueOnce(response({access_token:'rotated',refresh_token:'rotated-refresh'})).mockResolvedValueOnce(response({user:{id:1}}));vi.stubGlobal('fetch',fetcher);
  const {api}=await import('./api');expect(await api('auth/me')).toEqual({user:{id:1}});expect(fetcher).toHaveBeenCalledTimes(3);expect(fetcher.mock.calls[2][1].headers.Authorization).toBe('Bearer rotated');expect(storage.get('hrm.refresh')).toBe('rotated-refresh');
 });
 it('clears credentials when a refresh token is revoked',async()=>{
  storage.set('hrm.access','expired');storage.set('hrm.refresh','revoked');vi.stubGlobal('fetch',vi.fn().mockResolvedValueOnce(response({},401)).mockResolvedValueOnce(response({},401)));
  const {api}=await import('./api');await expect(api('employees')).rejects.toThrow('Phiên đăng nhập hết hạn.');expect(storage.has('hrm.access')).toBe(false);expect(storage.has('hrm.refresh')).toBe(false);
 });
 it('surfaces validation details without treating failed writes as success',async()=>{
  vi.stubGlobal('fetch',vi.fn().mockResolvedValue(response({error:{message:'Dữ liệu không hợp lệ.',details:{email:'Email không hợp lệ.'}}},422)));
  const {api}=await import('./api');await expect(api('employees','POST',{email:'invalid'})).rejects.toThrow('email: Email không hợp lệ.');
 });
 it('never retries an invalid login using an existing refresh token',async()=>{
  storage.set('hrm.refresh','old-session');const fetcher=vi.fn().mockResolvedValue(response({error:{message:'Sai mật khẩu.'}},401));vi.stubGlobal('fetch',fetcher);
  const {api}=await import('./api');await expect(api('auth/login','POST',{email:'test@local.test',password:'wrong'})).rejects.toThrow('Sai mật khẩu.');expect(fetcher).toHaveBeenCalledTimes(1);
 });
});
