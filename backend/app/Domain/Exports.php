<?php
namespace App\Domain;
use PhpOffice\PhpSpreadsheet\{Spreadsheet,IOFactory,Cell\DataType};
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
final class Exports
{
    public function __construct(private Store $s) {}
    public function template($response)
    {
        $this->s->ctx->require('people');$book=new Spreadsheet();$sheet=$book->getActiveSheet();
        $sheet->fromArray(['code','full_name','email','hire_date','employment_type','branch_id','department_id','job_title','salary_type','base_salary','allowance'],null,'A1');
        $sheet->getStyle('1:1')->getFont()->setBold(true);$sheet->freezePane('A2');
        $temp=tempnam(WRITEPATH,'template');(new Xlsx($book))->save($temp);$body=file_get_contents($temp);unlink($temp);
        return $response->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->setHeader('Content-Disposition','attachment; filename="employee-template.xlsx"')->setHeader('Cache-Control','no-store')->setBody($body);
    }
    public function download(string $resource,array $filters,$response)
    {
        $format=$filters['format']??'xlsx';if (!in_array($format,['xlsx','csv','pdf'])) throw new Problem(422,'Định dạng không hợp lệ.');
        $this->s->ctx->require('reports');
        if (isset(Schema::RESOURCES[$resource])) {
            $first=(new Resources($this->s))->list($resource,[...$filters,'limit'=>200,'page'=>1]);
            if($first['meta']['total']>10000)throw new Problem(422,'Tối đa 10.000 dòng mỗi lần xuất; hãy thu hẹp bộ lọc.');
            $rows=$first['data'];for($page=2;count($rows)<$first['meta']['total'];$page++)$rows=array_merge($rows,(new Resources($this->s))->list($resource,[...$filters,'limit'=>200,'page'=>$page])['data']);
        }
        else $rows=(new Reports($this->s))->run($resource,$filters)['data'];
        foreach($rows as &$r) { unset($r['inputs'],$r['emergency_contact'],$r['storage_key']);foreach($r as &$v) { $v=DateFormat::display($v);if (is_array($v)) $v=json_encode($v,JSON_UNESCAPED_UNICODE); } }unset($r,$v);
        $columns=array_keys($rows[0]??['message'=>'']);$mime='';$body='';
        if ($format==='pdf') {
            $html='<html><meta charset="utf-8"><style>body{font-family:DejaVu Sans;font-size:8px}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:4px;word-break:break-all}</style><h1>Mini HRM — '.htmlspecialchars($resource).'</h1><p>Ước tính lương chưa bao gồm đầy đủ nghĩa vụ pháp lý.</p><table><tr>';
            foreach($columns as $c) $html.='<th>'.htmlspecialchars($c).'</th>';$html.='</tr>';
            foreach($rows as $r) { $html.='<tr>';foreach($r as $v) $html.='<td>'.htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8').'</td>';$html.='</tr>'; }$html.='</table></html>';
            $pdf=new Dompdf(['isRemoteEnabled'=>false]);$pdf->loadHtml($html,'UTF-8');$pdf->setPaper('A3','landscape');$pdf->render();$body=$pdf->output();$mime='application/pdf';
        } elseif ($format==='csv') {
            $f=fopen('php://temp','w+');fwrite($f,"\xEF\xBB\xBF");fputcsv($f,$columns,',','"','');
            foreach($rows as $r) fputcsv($f,array_map(fn($v)=>preg_match('/^[=+@\-\t\r]/',(string)$v)?"'".$v:$v,array_values($r)),',','"','');rewind($f);$body=stream_get_contents($f);fclose($f);$mime='text/csv; charset=utf-8';
        } else {
            $book=new Spreadsheet();$sheet=$book->getActiveSheet();$sheet->fromArray($columns,null,'A1');
            foreach($rows as $i=>$r) { $j=1;foreach($r as $v) $sheet->setCellValueExplicit([$j++,$i+2],(string)$v,DataType::TYPE_STRING); }
            $sheet->getStyle('1:1')->getFont()->setBold(true);$sheet->freezePane('A2');$temp=tempnam(WRITEPATH,'export');(new Xlsx($book))->save($temp);$body=file_get_contents($temp);unlink($temp);$mime='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
        $this->s->audit('export',$resource,0,null,['format'=>$format,'rows'=>count($rows)]);
        return $response->setContentType($mime)->setHeader('Cache-Control','no-store')->setHeader('Content-Disposition','attachment; filename="minihrm-'.$resource.'.'.$format.'"')->setBody($body);
    }
    public function import(array $v): array
    {
        $this->s->ctx->require('people');(new Subscriptions($this->s))->writable('export');
        $data=base64_decode($v['content']??'',true);if (!$data||strlen($data)>2*1024*1024) throw new Problem(422,'Tệp XLSX tối đa 2 MB.');
        $temp=tempnam(WRITEPATH,'import');file_put_contents($temp,$data);
        try {
            $zip=new \ZipArchive();if ($zip->open($temp)!==true||$zip->numFiles>2000) throw new Problem(422,'XLSX không hợp lệ.');$size=0;for($i=0;$i<$zip->numFiles;$i++) $size+=$zip->statIndex($i)['size'];$zip->close();if ($size>20*1024*1024) throw new Problem(422,'Tệp giải nén quá lớn.');
            $reader=IOFactory::createReader('Xlsx');$reader->setReadDataOnly(true);$rows=$reader->load($temp)->getActiveSheet()->toArray(null,false,false,false);
            $header=array_shift($rows);if (!$header||array_diff($header,array_keys(Schema::RESOURCES['employees']))||count($rows)>500) throw new Problem(422,'Tên cột phải khớp mẫu nhập nhân viên; tối đa 500 dòng.');
            return $this->s->transaction(function() use($rows,$header) { $this->s->lockTenant();$count=0;foreach($rows as $i=>$row) { if (!array_filter($row,fn($v)=>$v!==null&&$v!=='')) continue;
                try { (new Resources($this->s))->save('employees',array_combine($header,array_slice(array_pad($row,count($header),null),0,count($header))));$count++; }catch(Problem $e) { throw new Problem($e->status,'Dòng '.($i+2).': '.$e->getMessage(),$e->details); }
            }return ['imported'=>$count]; });
        } finally { unlink($temp); }
    }
}
