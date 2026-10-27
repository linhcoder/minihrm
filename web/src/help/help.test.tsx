// @vitest-environment jsdom
import React from 'react';
import {afterEach,beforeEach,describe,it,expect,vi} from 'vitest';
import {render,screen,fireEvent,waitFor,cleanup} from '@testing-library/react';
import {MemoryRouter,Routes,Route} from 'react-router-dom';
import {QueryClient,QueryClientProvider} from '@tanstack/react-query';
import {GuidanceProvider,HelpLauncher,placeCoach} from './Guidance';
import HelpCenter from './HelpCenter';
import {articles,canRead,searchArticles,tourSteps,storageKey,type Audience} from './catalog';
import {api} from '../api';
vi.mock('../api',()=>({api:vi.fn()}));
const owner:Audience={userId:1,tenantId:1,role:'owner',superUser:false,permissions:[]};
const employee={...owner,userId:2,role:'employee'};
const session={user:{id:1,is_super:0},tenant:{id:1},membership:{role:'owner',permissions:'[]'}};
beforeEach(()=>{localStorage.clear();vi.mocked(api).mockReset();vi.mocked(api).mockImplementation(async(_p,method,body)=>method==='PUT'?body:{version:1,status:'completed'});window.scrollTo=vi.fn();Element.prototype.scrollIntoView=vi.fn();});
afterEach(cleanup);
function mount(path='/help'){return render(<MemoryRouter initialEntries={[path]}><QueryClientProvider client={new QueryClient({defaultOptions:{queries:{retry:false}}})}><GuidanceProvider session={session} onNavigationChange={()=>{}}><HelpLauncher/><Routes><Route path="/help/:articleId?" element={<HelpCenter/>}/><Route path="*" element={<div data-tour="overview">Tổng quan</div>}/></Routes></GuidanceProvider></QueryClientProvider></MemoryRouter>);}
describe('guide content and permissions',()=>{
 it('finds Vietnamese content without accents and filters topics',()=>{expect(searchArticles('them nhan vien',owner).some(a=>a.id==='them-nhan-vien')).toBe(true);expect(searchArticles('',owner,'people').every(a=>a.topic==='people')).toBe(true);});
 it('keeps management and platform articles scoped to the role',()=>{expect(canRead(articles.find(a=>a.id==='lap-bang-luong')!,employee)).toBe(false);expect(tourSteps(employee).some(s=>s.id==='payroll'||s.id==='employees')).toBe(false);expect(searchArticles('',owner).some(a=>a.topic==='platform')).toBe(false);});
 it('has unique article IDs, four steps and valid related links',()=>{expect(new Set(articles.map(a=>a.id)).size).toBe(articles.length);for(const a of articles){expect(a.steps.length).toBe(4);for(const id of a.related)expect(articles.some(b=>b.id===id)).toBe(true);}});
 it('separates browser preferences and clamps mobile coach',()=>{expect(storageKey(owner)).not.toBe(storageKey(employee));const p=placeCoach({left:300,top:600,width:60,height:80},{width:360,height:740},{width:370,height:300});expect(p.left+p.width).toBeLessThanOrEqual(348);expect(p.top).toBeGreaterThanOrEqual(12);});
});
describe('help interactions',()=>{
 it('searches and clears an empty result',async()=>{mount();fireEvent.change(screen.getByLabelText('Tìm hướng dẫn'),{target:{value:'zzzzzz'}});expect(screen.getByText('Chưa tìm thấy hướng dẫn phù hợp')).toBeTruthy();fireEvent.click(screen.getByText('Xem tất cả hướng dẫn'));expect(screen.getByText('Hướng dẫn dành cho bạn')).toBeTruthy();});
 it('opens illustrated steps and marks an article understood',()=>{mount('/help/them-nhan-vien');expect(screen.getByRole('img')).toBeTruthy();expect(screen.getByText('Cách thực hiện')).toBeTruthy();fireEvent.click(screen.getByText('Đánh dấu đã hiểu'));expect(screen.getByText('Đã hiểu bài này')).toBeTruthy();fireEvent.click(screen.getByLabelText('Phóng to hình minh họa'));expect(screen.getByRole('dialog')).toBeTruthy();});
 it('offers first-visit tour and persists skip for the current user',async()=>{vi.mocked(api).mockResolvedValue({version:0,status:'new'});const view=mount('/');await screen.findByText('Làm quen một chút nhé?');fireEvent.click(screen.getByText('Để sau, tôi muốn tự khám phá'));await waitFor(()=>expect(api).toHaveBeenCalledWith('me/guidance','PUT',{version:1,status:'dismissed'}));view.unmount();mount('/');await waitFor(()=>expect(api).toHaveBeenCalled());expect(screen.queryByText('Làm quen một chút nhé?')).toBeNull();});
 it('replays the tour and navigates steps without business writes',async()=>{mount();fireEvent.click(screen.getByText('Bắt đầu tour'));await screen.findByRole('heading',{name:'Bắt đầu từ tổng quan'});fireEvent.click(screen.getByText('Tiếp theo'));await screen.findByRole('heading',{name:'Các chức năng được chia theo nhóm'});fireEvent.click(screen.getByLabelText('Bước trước'));await screen.findByRole('heading',{name:'Bắt đầu từ tổng quan'});fireEvent.click(screen.getByText('Bỏ qua tour'));expect(vi.mocked(api).mock.calls.every(([path,method])=>!method||path==='me/guidance')).toBe(true);});
});
