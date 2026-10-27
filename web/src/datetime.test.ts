import {describe,it,expect} from 'vitest';
import {formatDateTime,toIsoDate,formatDateTree} from './datetime';
describe('Vietnamese date and time presentation',()=>{
 it('formats dates without shifting the calendar day',()=>{expect(formatDateTime('2026-10-27')).toBe('27/10/2026');});
 it('converts UTC across midnight to Vietnam time',()=>{expect(formatDateTime('2026-10-27T18:05:09Z')).toBe('28/10/2026 01:05:09');});
 it('handles offsets and midnight with a 24-hour clock',()=>{expect(formatDateTime('2026-10-27T00:00:00+07:00')).toBe('27/10/2026 00:00:00');});
 it('pads seconds on shift times',()=>{expect(formatDateTime('08:05')).toBe('08:05:00');expect(formatDateTime('08:05:10')).toBe('08:05:10');});
 it('rejects invalid Vietnamese dates and accepts leap days',()=>{expect(toIsoDate('29/02/2024')).toBe('2024-02-29');expect(toIsoDate('31/02/2026')).toBe('31/02/2026');});
 it('formats nested audit data without changing identifiers or notes',()=>{expect(formatDateTree({id:27,at:'2026-10-27T01:00:00Z',notes:'Đơn ngày 27/10'})).toEqual({id:27,at:'27/10/2026 08:00:00',notes:'Đơn ngày 27/10'});});
});
