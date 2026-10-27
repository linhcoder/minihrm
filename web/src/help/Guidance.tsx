import {createContext,useCallback,useContext,useEffect,useMemo,useRef,useState,type ReactNode} from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import {useLocation,useNavigate,Link} from 'react-router-dom';
import {useQuery,useQueryClient} from '@tanstack/react-query';
import {ArrowLeft,ArrowRight,BookOpen,Check,ChevronDown,HelpCircle,Play,RotateCcw,Sparkles,X} from 'lucide-react';
import {api} from '../api';
import {audienceFromSession,GUIDE_VERSION,storageKey,tourSteps,type Audience,type TourStep} from './catalog';
import './help.css';

type Preference={version:number;status:'new'|'dismissed'|'completed';updated_at?:string|null};
type GuidanceValue={audience:Audience;startTour:()=>void;readArticles:string[];markRead:(id:string)=>void};
const GuidanceContext=createContext<GuidanceValue|null>(null);
export function useGuidance(){const context=useContext(GuidanceContext);if(!context)throw Error('GuidanceProvider is required');return context;}
export function readPreference(key:string):Preference|null{try{const v=JSON.parse(localStorage.getItem(key)||'null');return v&&v.version===GUIDE_VERSION&&['completed','dismissed'].includes(v.status)?v:null;}catch{return null;}}
function storePreference(key:string,value:Preference){try{localStorage.setItem(key,JSON.stringify(value));}catch{/* Server persistence still works if browser storage is disabled. */}}
function readProgress(key:string):string[]{try{const value=JSON.parse(localStorage.getItem(key+':read')||'[]');return Array.isArray(value)?value.filter((s:unknown)=>typeof s==='string'):[];}catch{return [];}}

export type Rect={left:number;top:number;width:number;height:number};
export function placeCoach(rect:Rect|null,viewport:{width:number;height:number},size:{width:number;height:number},navigation=false):{left:number;top:number;width:number}{
 const padding=12;const width=Math.min(size.width,Math.max(0,viewport.width-padding*2));const clamp=(v:number,min:number,max:number)=>Math.max(min,Math.min(v,Math.max(min,max)));
 let left=(viewport.width-width)/2,top=(viewport.height-size.height)/2;
 if(rect){
  if(navigation&&rect.left+rect.width+width+28<viewport.width){left=rect.left+rect.width+16;top=rect.top+24;}
  else{left=clamp(rect.left,padding,viewport.width-width-padding);top=rect.top+rect.height+14;if(top+size.height>viewport.height-padding)top=rect.top-size.height-14;if(top<padding)top=viewport.height-size.height-padding;}
 }
 return {left:clamp(left,padding,viewport.width-width-padding),top:clamp(top,padding,viewport.height-size.height-padding),width};
}
function useTarget(step:TourStep,ready:boolean,retry:number){
 const [rect,setRect]=useState<Rect|null>(null);const [missing,setMissing]=useState(false);
 useEffect(()=>{
  setRect(null);setMissing(false);if(!ready)return;
  let alive=true,frame=0,timer=0,scrolled=false;const deadline=Date.now()+5500;
  const measure=()=>{if(!alive)return;const element=document.querySelector<HTMLElement>(step.target);
   if(element){let box=element.getBoundingClientRect();if(box.width>0&&box.height>0){if(!scrolled){element.scrollIntoView?.({block:'center',inline:'nearest',behavior:'instant'});scrolled=true;box=element.getBoundingClientRect();}
    const left=Math.max(5,box.left-5),top=Math.max(5,box.top-5),right=Math.min(window.innerWidth-5,box.right+5),bottom=Math.min(window.innerHeight-5,box.bottom+5);
    if(right>left&&bottom>top){const next={left,top,width:right-left,height:bottom-top};setRect(old=>old&&Object.keys(next).every(k=>old[k as keyof Rect]===next[k as keyof Rect])?old:next);setMissing(false);return;}
   }}
   setRect(null);if(Date.now()>=deadline){setMissing(true);return;}window.clearTimeout(timer);timer=window.setTimeout(measure,100);
  };
  const update=()=>{cancelAnimationFrame(frame);frame=requestAnimationFrame(measure);};
  const observer=new MutationObserver(update);const surfaces=[document.querySelector('.app-content'),document.querySelector('.sidebar')].filter(Boolean);if(!surfaces.length)surfaces.push(document.body);
  surfaces.forEach(surface=>observer.observe(surface!,{childList:true,subtree:true,attributes:true,attributeFilter:['class']}));
  const sizeObserver=typeof ResizeObserver!=='undefined'?new ResizeObserver(update):null;surfaces.forEach(surface=>sizeObserver?.observe(surface!));
  window.addEventListener('resize',update);window.addEventListener('scroll',update,true);update();
  return()=>{alive=false;cancelAnimationFrame(frame);window.clearTimeout(timer);observer.disconnect();sizeObserver?.disconnect();window.removeEventListener('resize',update);window.removeEventListener('scroll',update,true);};
 },[step.id,step.target,ready,retry]);return {rect,missing};
}

export function GuidanceProvider({session,onNavigationChange,children}:{session:any;onNavigationChange:(open:boolean)=>void;children:ReactNode}){
 const audience=useMemo(()=>audienceFromSession(session),[session]);const key=storageKey(audience);const qc=useQueryClient();const location=useLocation();const navigate=useNavigate();
 const preference=useQuery<Preference>({queryKey:['guidance',key],queryFn:()=>api('me/guidance'),staleTime:Infinity,retry:1,refetchOnWindowFocus:false});
 const [welcome,setWelcome]=useState(false);const [active,setActive]=useState(false);const [index,setIndex]=useState(0);const [retry,setRetry]=useState(0);const initialized=useRef('');const startButton=useRef<HTMLButtonElement>(null);
 const [progress,setProgress]=useState<{key:string;ids:string[]}>(()=>({key,ids:readProgress(key)}));const readArticles=progress.key===key?progress.ids:readProgress(key);
 const steps=useMemo(()=>tourSteps(audience),[audience]);const step=steps[Math.min(index,steps.length-1)];
 useEffect(()=>{if(preference.isPending||initialized.current===key||active)return;const home=audience.superUser?'/super':'/';const path=location.pathname.replace(/\/$/,'')||'/';if(path!==home&&path!=='/help')return;
  if(document.querySelector('[role="dialog"][data-state="open"]'))return;initialized.current=key;
  const local=readPreference(key);const remote=preference.data;
  if(remote&&remote.version>=GUIDE_VERSION&&remote.status!=='new'){storePreference(key,remote);return;}
  if(local){if(remote?.status==='new')void api('me/guidance','PUT',{version:GUIDE_VERSION,status:local.status}).then(v=>qc.setQueryData(['guidance',key],v)).catch(()=>{});return;}
  setWelcome(true);
 },[preference.isPending,preference.data,key,active,audience.superUser,location.pathname,qc]);
 const finish=useCallback((status:'dismissed'|'completed')=>{setActive(false);setWelcome(false);onNavigationChange(false);initialized.current=key;const value:Preference={version:GUIDE_VERSION,status};storePreference(key,value);qc.setQueryData(['guidance',key],value);void api('me/guidance','PUT',value).then(v=>qc.setQueryData(['guidance',key],v)).catch(()=>{});requestAnimationFrame(()=>document.querySelector<HTMLButtonElement>('[data-tour="help-trigger"]')?.focus());},[key,qc,onNavigationChange]);
 const startTour=useCallback(()=>{initialized.current=key;setWelcome(false);setIndex(0);setRetry(0);setActive(true);},[key]);
 const markRead=useCallback((id:string)=>{const previous=readProgress(key);const ids=previous.includes(id)?previous.filter(v=>v!==id):[...previous,id];try{localStorage.setItem(key+':read',JSON.stringify(ids));}catch{}setProgress({key,ids});},[key]);
 useEffect(()=>{if(!active)return;onNavigationChange(!!step.navigation&&window.innerWidth<=900);if((location.pathname.replace(/\/$/,'')||'/')!==step.route)navigate(step.route);},[active,step.id,step.route,step.navigation,location.pathname,navigate,onNavigationChange]);
 const {rect,missing}=useTarget(step,active&&(location.pathname.replace(/\/$/,'')||'/')===step.route,retry);
 const [viewport,setViewport]=useState({width:window.innerWidth,height:window.innerHeight});const panel=useRef<HTMLDivElement>(null);const [size,setSize]=useState({width:370,height:300});
 useEffect(()=>{const update=()=>setViewport({width:window.innerWidth,height:window.innerHeight});window.addEventListener('resize',update);return()=>window.removeEventListener('resize',update);},[]);
 useEffect(()=>{if(!active||!panel.current)return;const element=panel.current;const update=()=>{const box=element.getBoundingClientRect();if(box.width&&box.height)setSize(old=>old.width===box.width&&old.height===box.height?old:{width:box.width,height:box.height});};const observer=typeof ResizeObserver!=='undefined'?new ResizeObserver(update):null;observer?.observe(element);update();return()=>observer?.disconnect();},[active,index,missing]);
 const position=placeCoach(rect,viewport,size,step.navigation);const next=()=>index===steps.length-1?finish('completed'):setIndex(i=>i+1);
 return <GuidanceContext.Provider value={{audience,startTour,readArticles,markRead}}>{children}
  <Dialog.Root open={welcome} onOpenChange={open=>{if(!open)finish('dismissed');}}><Dialog.Portal><Dialog.Overlay className="guide-welcome-overlay"/><Dialog.Content className="guide-welcome" onOpenAutoFocus={e=>{e.preventDefault();startButton.current?.focus({preventScroll:true});}}><Dialog.Close className="guide-close" aria-label="Để hướng dẫn sau"><X size={20}/></Dialog.Close><div className="guide-welcome-art" aria-hidden="true"><span><BookOpen size={38}/></span><i><Check size={17}/></i><b><Sparkles size={18}/></b></div><span className="help-eyebrow">CHÀO MỪNG ĐẾN MINI HRM</span><Dialog.Title>Làm quen một chút nhé?</Dialog.Title><Dialog.Description className="guide-description">Đi một vòng khoảng một phút để biết nơi tìm chức năng và các thao tác cơ bản dành cho bạn.</Dialog.Description><div className="guide-welcome-points"><span><Check size={15}/>Chỉ dẫn ngay trên màn hình</span><span><Check size={15}/>Có thể bỏ qua và xem lại</span><span><Check size={15}/>Không tự tạo hay thay đổi dữ liệu</span></div><button className="primary wide" ref={startButton} onClick={startTour}><Play size={17}/>Bắt đầu hướng dẫn</button><button className="guide-later" onClick={()=>finish('dismissed')}>Để sau, tôi muốn tự khám phá</button><p>Bạn luôn có thể mở lại ở mục Hướng dẫn sử dụng.</p></Dialog.Content></Dialog.Portal></Dialog.Root>
  <Dialog.Root open={active} onOpenChange={open=>{if(!open)finish('dismissed');}}><Dialog.Portal><Dialog.Overlay className="guide-tour-overlay"><svg width="100%" height="100%" aria-hidden="true"><defs><mask id="minihrm-guide-spotlight"><rect width="100%" height="100%" fill="white"/>{rect&&<rect width={rect.width} height={rect.height} x={rect.left} y={rect.top} rx="10" fill="black"/>}</mask></defs><rect width="100%" height="100%" fill="rgba(10,22,46,.64)" mask="url(#minihrm-guide-spotlight)"/></svg></Dialog.Overlay>{rect&&<div className="guide-spotlight-outline" aria-hidden="true" style={{left:rect.left,top:rect.top,width:rect.width,height:rect.height}}/>}<Dialog.Content ref={panel} className="guide-coach" style={position} onInteractOutside={e=>e.preventDefault()} onKeyDown={e=>{if(e.key==='ArrowRight'){e.preventDefault();next();}if(e.key==='ArrowLeft'&&index>0){e.preventDefault();setIndex(i=>i-1);}}}>
   <div className="guide-coach-top"><span><Sparkles size={13}/>BƯỚC {index+1}/{steps.length}</span><Dialog.Close aria-label="Kết thúc hướng dẫn"><X size={18}/></Dialog.Close></div><div className="guide-coach-progress" aria-hidden="true"><i style={{width:`${(index+1)/steps.length*100}%`}}/></div><Dialog.Title>{step.title}</Dialog.Title><Dialog.Description className="guide-description">{step.body}</Dialog.Description>{step.note&&<p className="guide-coach-note">{step.note}</p>}
   {!rect&&<p className="guide-target-state" role="status">{missing?'Khu vực này chưa tải được. Bạn có thể thử lại hoặc chuyển sang bước tiếp theo.':'Đang tìm vị trí trên màn hình…'}{missing&&<button onClick={()=>setRetry(v=>v+1)}><RotateCcw size={12}/>Thử lại</button>}</p>}
   <div className="guide-coach-actions"><button className="guide-skip" onClick={()=>finish('dismissed')}>Bỏ qua tour</button><div><button aria-label="Bước trước" disabled={index===0} onClick={()=>setIndex(i=>i-1)}><ArrowLeft size={16}/></button><button className="primary" onClick={next}>{index===steps.length-1?'Hoàn tất':'Tiếp theo'}{index===steps.length-1?<Check size={15}/>:<ArrowRight size={15}/>}</button></div></div><span className="guide-keyboard-hint">← → để chuyển bước · Esc để kết thúc</span>
  </Dialog.Content></Dialog.Portal></Dialog.Root>
 </GuidanceContext.Provider>;
}

export function HelpLauncher(){const [open,setOpen]=useState(false);const {startTour}=useGuidance();useEffect(()=>{if(!open)return;const close=(e:KeyboardEvent)=>{if(e.key==='Escape')setOpen(false);};window.addEventListener('keydown',close);return()=>window.removeEventListener('keydown',close);},[open]);return <div className="help-launcher"><button className="icon-button" data-tour="help-trigger" aria-label="Trợ giúp và hướng dẫn" aria-expanded={open} onClick={()=>setOpen(v=>!v)}><HelpCircle size={20}/><ChevronDown size={10}/></button>{open&&<><button className="help-menu-dismiss" aria-label="Đóng menu trợ giúp" onClick={()=>setOpen(false)}/><div className="help-launcher-menu"><Link to="/help" onClick={()=>setOpen(false)}><BookOpen size={17}/><span><strong>Hướng dẫn sử dụng</strong><small>Tra cứu chủ đề, xem từng bước</small></span></Link><button onClick={()=>{setOpen(false);startTour();}}><Play size={17}/><span><strong>Xem lại tour thao tác</strong><small>Chỉ dẫn ngay trên giao diện</small></span></button></div></>}</div>}
