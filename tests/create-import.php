<?php
require __DIR__.'/../backend/vendor/autoload.php';
$book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$book->getActiveSheet()->fromArray([
    ['code','full_name','email','hire_date','employment_type'],
    ['IMPORT-1','Nhân viên nhập Excel','import1@company.test','01/01/2026','full_time'],
    ['IMPORT-2','Nhân viên nhập Excel 2','import2@company.test','01/01/2026','part_time'],
]);
(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($argv[1]);
