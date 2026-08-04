<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class CompanyNoticeZipValidator
{
 public const MAX_ENTRIES=5000;
 public const MAX_EXPANDED_BYTES=2147483648;
 public const MAX_ENTRY_BYTES=524288000;
 public const MAX_COMPRESSION_RATIO=100;
 public const MAX_PATH_DEPTH=20;
 private const PROHIBITED=['exe','com','bat','cmd','msi','msp','scr','pif','ps1','psm1','vbs','vbe','js','jse','wsf','wsh','hta','jar','sh','bash','php','phar','py','pl','rb','cgi','dll','so','dylib','app','apk','deb','rpm','docm','dotm','xlsm','xltm','pptm','potm','ppam','sldm'];
 /** @return array{entries:int,expanded_bytes:int} */
 public function validate(string $path,string $filename,int $reportedBytes,int $uploadError=UPLOAD_ERR_OK):array
 {
  if($uploadError!==UPLOAD_ERR_OK)throw new \InvalidArgumentException('The ZIP upload did not complete successfully.');
  if($reportedBytes<1||$reportedBytes>CompanyNoticeAssetPolicy::MAX_OBJECT_BYTES)throw new \InvalidArgumentException('The ZIP file must be no larger than 100 MB.');
  if(strtolower((string)pathinfo($filename,PATHINFO_EXTENSION))!=='zip'||!is_file($path))throw new \InvalidArgumentException('The uploaded file is not an approved ZIP archive.');
  $actual=filesize($path);if($actual===false||$actual!==$reportedBytes)throw new \InvalidArgumentException('The ZIP upload size could not be verified.');
  $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['application/zip','application/x-zip','application/x-zip-compressed'],true))throw new \InvalidArgumentException('The uploaded file is not an approved ZIP archive.');
  $signature=file_get_contents($path,false,null,0,4);if(!in_array($signature,["PK\x03\x04","PK\x05\x06","PK\x07\x08"],true))throw new \InvalidArgumentException('The ZIP signature is invalid.');
  $zip=new \ZipArchive();if($zip->open($path)!==true)throw new \InvalidArgumentException('The ZIP archive is malformed or unreadable.');
  try{
   if($zip->numFiles>self::MAX_ENTRIES)throw new \InvalidArgumentException('The ZIP archive contains too many entries.');$expanded=0;
   for($i=0;$i<$zip->numFiles;$i++){$entry=$zip->statIndex($i);if(!is_array($entry))throw new \InvalidArgumentException('A ZIP entry could not be inspected.');$name=(string)($entry['name']??'');$this->validateName($name);$encryption=(int)($entry['encryption_method']??0);if($encryption!==0)throw new \InvalidArgumentException('Encrypted ZIP archives are not allowed.');$size=(int)($entry['size']??0);$compressed=(int)($entry['comp_size']??0);if($size>self::MAX_ENTRY_BYTES)throw new \InvalidArgumentException('A ZIP entry exceeds the expanded-size limit.');$expanded+=$size;if($expanded>self::MAX_EXPANDED_BYTES)throw new \InvalidArgumentException('The ZIP archive exceeds the expanded-size limit.');if($size>0&&$compressed===0)throw new \InvalidArgumentException('The ZIP archive has a suspicious compression ratio.');if($compressed>0&&$size/$compressed>self::MAX_COMPRESSION_RATIO)throw new \InvalidArgumentException('The ZIP archive has a suspicious compression ratio.');$extension=strtolower((string)pathinfo($name,PATHINFO_EXTENSION));if(in_array($extension,self::PROHIBITED,true))throw new \InvalidArgumentException('The ZIP archive contains a prohibited file type.');}
   return ['entries'=>$zip->numFiles,'expanded_bytes'=>$expanded];
  }finally{$zip->close();}
 }
 private function validateName(string $name):void
 {
  if($name===''||str_contains($name,"\0")||str_starts_with($name,'/')||str_starts_with($name,'\\')||preg_match('/^[A-Za-z]:[\\\\\/]/',$name))throw new \InvalidArgumentException('The ZIP archive contains an unsafe entry path.');$normalized=str_replace('\\','/',$name);$parts=explode('/',$normalized);if(in_array('..',$parts,true)||count(array_filter($parts,static fn(string $part):bool=>$part!==''))>self::MAX_PATH_DEPTH)throw new \InvalidArgumentException('The ZIP archive contains an unsafe entry path.');
 }
}
