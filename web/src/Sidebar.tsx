import {useEffect,useId,useState,type ReactNode} from 'react';
import {createPortal} from 'react-dom';
import {NavLink} from 'react-router-dom';
import {Menu} from 'lucide-react';
import './sidebar.css';

export function useSidebarCollapsed(){
 const [collapsed,setCollapsed]=useState(()=>{try{return localStorage.getItem('minihrm:sidebar-collapsed')==='1';}catch{return false;}});
 useEffect(()=>{try{localStorage.setItem('minihrm:sidebar-collapsed',collapsed?'1':'0');}catch{}},[collapsed]);
 return [collapsed,setCollapsed] as const;
}

export function SidebarToggle({collapsed,onToggle}:{collapsed:boolean;onToggle:()=>void}){
 const title=collapsed?'Mở rộng menu':'Thu gọn menu';
 return <button className="sidebar-toggle" onClick={onToggle} aria-label={title} aria-expanded={!collapsed} title={title}><Menu size={19}/></button>;
}

export function SidebarLink({to,title,collapsed,onClick,children}:{to:string;title:string;collapsed:boolean;onClick:()=>void;children:ReactNode}){
 const id=useId();const [position,setPosition]=useState<{left:number;top:number}|null>(null);
 const show=(element:HTMLElement)=>{if(!collapsed||window.innerWidth<=900)return;const rect=element.getBoundingClientRect();setPosition({left:rect.right+12,top:rect.top+rect.height/2});};
 useEffect(()=>{setPosition(null);},[collapsed]);
 useEffect(()=>{if(!position)return;const close=()=>setPosition(null);const escape=(e:KeyboardEvent)=>{if(e.key==='Escape')close();};window.addEventListener('scroll',close,true);window.addEventListener('resize',close);window.addEventListener('keydown',escape);return()=>{window.removeEventListener('scroll',close,true);window.removeEventListener('resize',close);window.removeEventListener('keydown',escape);};},[position]);
 return <><NavLink to={to} end aria-label={title} aria-describedby={position?id:undefined} onClick={()=>{setPosition(null);onClick();}} onMouseEnter={e=>show(e.currentTarget)} onMouseLeave={()=>setPosition(null)} onFocus={e=>show(e.currentTarget)} onBlur={()=>setPosition(null)}>{children}<span className="nav-label">{title}</span></NavLink>{position&&createPortal(<div id={id} role="tooltip" className="sidebar-tooltip" style={position}>{title}</div>,document.body)}</>;
}
