<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Exceptions\Domain\InvalidCallLogWorkbookException;
use NpmGateway\Support\PhoneFormatter;
use PhpOffice\PhpSpreadsheet\IOFactory;
final readonly class CallLogWorkbookParser
{
 public const HEADERS=['Calling TN','Called TN','Start Time','Release Time','Call Duration'];
 public function __construct(private PhoneFormatter $phones){}
 public function parse(array$file):array
 {
  if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new InvalidCallLogWorkbookException('Choose a Lumen Detailed Report (.xls) to upload.');$path=(string)($file['tmp_name']??'');$name=basename((string)($file['name']??''));if(!is_file($path)||filesize($path)<1)throw new InvalidCallLogWorkbookException('The uploaded Call Log file is empty or unavailable.');if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='xls')throw new InvalidCallLogWorkbookException('Upload the Lumen Detailed Report in .xls format.');
  try{$type=IOFactory::identify($path);}catch(\Throwable){throw new InvalidCallLogWorkbookException('The file is not a readable Lumen Excel workbook.');}if($type!=='Xls')throw new InvalidCallLogWorkbookException('The uploaded file is not a genuine Excel .xls workbook.');
  try{$reader=IOFactory::createReader('Xls');$reader->setReadDataOnly(true);$book=$reader->load($path);}catch(\Throwable){throw new InvalidCallLogWorkbookException('The Lumen Excel workbook could not be read safely.');}
  try{if(!in_array('Detailed Report',$book->getSheetNames(),true))throw new InvalidCallLogWorkbookException('Expected worksheet "Detailed Report" was not found.');$sheet=$book->getSheetByName('Detailed Report');$headers=[];for($c=1;$c<=5;$c++)$headers[]=trim((string)$sheet->getCell([$c,1])->getValue());if($headers!==self::HEADERS||$sheet->getHighestDataColumn(1)!=='E')throw new InvalidCallLogWorkbookException('Detailed Report must contain exactly: '.implode(', ',self::HEADERS).'.');$rows=[];for($r=2;$r<=$sheet->getHighestDataRow();$r++){ $raw=[];for($c=1;$c<=5;$c++)$raw[]=$sheet->getCell([$c,$r])->getValue();if(count(array_filter($raw,static fn($v)=>$v!==null&&trim((string)$v)!==''))===0)continue;if(count(array_filter($raw,static fn($v)=>$v===null||trim((string)$v)===''))>0)throw new InvalidCallLogWorkbookException("Row {$r} is missing a required value.");$calling=$this->phones->normalize((string)$raw[0]);$called=$this->phones->normalize((string)$raw[1]);if($calling===null||$called===null)throw new InvalidCallLogWorkbookException("Row {$r} contains an invalid Calling TN or Called TN.");$start=$this->time((string)$raw[2],$r,'Start Time');$release=$this->time((string)$raw[3],$r,'Release Time');if($release<$start)throw new InvalidCallLogWorkbookException("Row {$r} has a Release Time before Start Time.");$durationRaw=trim((string)$raw[4]);if(preg_match('/^\d+(?:\.\d{1,3})?$/D',$durationRaw)!==1||(float)$durationRaw>999999999.999)throw new InvalidCallLogWorkbookException("Row {$r} has an invalid Call Duration.");$duration=number_format((float)$durationRaw,3,'.','');$elapsed=(float)$release->format('U.u')-(float)$start->format('U.u');if(abs($elapsed-(float)$duration)>0.01)throw new InvalidCallLogWorkbookException("Row {$r} has a materially inconsistent Call Duration.");$rows[]=['calling_tn'=>$calling,'called_tn'=>$called,'started_at'=>$start->format('Y-m-d H:i:s.v'),'released_at'=>$release->format('Y-m-d H:i:s.v'),'call_duration_seconds'=>$duration]; }
   if($rows===[])throw new InvalidCallLogWorkbookException('The Detailed Report contains no call records.');return['original_filename'=>$name,'file_sha256'=>hash_file('sha256',$path),'rows'=>$rows,'source_started_at'=>min(array_column($rows,'started_at')),'source_ended_at'=>max(array_column($rows,'released_at'))];
  }finally{$book->disconnectWorksheets();}
 }
 private function time(string$value,int$row,string$field):\DateTimeImmutable
 {foreach(['Ymd H:i:s.v','m/d/Y H:i:s.v','m/d/Y h:i:s.v A','m/d/Y H:i:s','m/d/Y h:i:s A','Y-m-d H:i:s.v','Y-m-d H:i:s']as$format){$date=\DateTimeImmutable::createFromFormat('!'.$format,trim($value));$errors=\DateTimeImmutable::getLastErrors();if($date!==false&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0)))return$date;}throw new InvalidCallLogWorkbookException("Row {$row} has an invalid {$field}.");}
}
