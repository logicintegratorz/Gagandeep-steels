<?php
namespace App\Http\Controllers;

use App\Models\LeadSetting;
use App\Support\DashboardContacts;
use Illuminate\Http\Request;
use ZipArchive;

final class DashboardContactsExportController
{
    public function __invoke(Request $request)
    {
        $query=DashboardContacts::query($request)->orderBy('leads.id');
        $settings=LeadSetting::find(1);
        $sheetPath=tempnam(sys_get_temp_dir(),'crm-sheet-');
        $bookPath=tempnam(sys_get_temp_dir(),'crm-book-');
        if($sheetPath===false || $bookPath===false)abort(500,'Could not prepare the contacts backup.');

        try {
            $sheet=fopen($sheetPath,'wb');
            if($sheet===false)abort(500,'Could not prepare the contacts backup.');
            fwrite($sheet,'<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
            $writeRow=function(array $values,int $number) use($sheet):void {
                fwrite($sheet,'<row r="'.$number.'">');
                foreach($values as $index=>$value){
                    $column='';$position=$index+1;
                    while($position>0){$position--; $column=chr(65+$position%26).$column; $position=intdiv($position,26);}
                    $safe=htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u','',(string)($value??''))??'',ENT_XML1|ENT_QUOTES,'UTF-8');
                    fwrite($sheet,'<c r="'.$column.$number.'" t="inlineStr"><is><t xml:space="preserve">'.$safe.'</t></is></c>');
                }
                fwrite($sheet,'</row>');
            };
            $writeRow(['Lead / Contact','Status','Company','Stage','Phone','Location','Source','Category','Lead Owner','Added','Score / 100','Follow-up Count','Email','WhatsApp Number','Website','Job Role','Saved as Contact','Notes'],1);
            $row=2;
            foreach($query->cursor() as $lead){
                $status=$lead->contact_saved_at?'Saved contact':($lead->owner_id===null?'Untouched':'Assigned lead');
                $writeRow([$lead->name,$status,$lead->company,$lead->stage,$lead->phone,$lead->location,$lead->source,$lead->category,$lead->owner?->name,$lead->created_at?->format('Y-m-d H:i'),$settings?->score($lead)??0,$lead->follow_up_count,$lead->email,$lead->whatsapp,$lead->website,$lead->job_role,$lead->contact_saved_at,$lead->notes],$row++);
            }
            fwrite($sheet,'</sheetData></worksheet>');
            fclose($sheet);

            $zip=new ZipArchive();
            if($zip->open($bookPath,ZipArchive::OVERWRITE)!==true)abort(500,'Could not prepare the contacts backup.');
            $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Contacts" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $zip->addFile($sheetPath,'xl/worksheets/sheet1.xml');
            if(!$zip->close())abort(500,'Could not prepare the contacts backup.');
            return response()->download($bookPath,'contacts-backup-'.now('Asia/Kolkata')->format('Y-m-d-His').'.xlsx',['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
        } finally {
            @unlink($sheetPath);
        }
    }
}
