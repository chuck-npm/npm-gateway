<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Support\CompanyNoticePresentation;
final class StorageUploadValidator
{
 public function __construct(private readonly CompanyNoticeZipValidator $zip=new CompanyNoticeZipValidator()){}
 public function validate(array $file,string $role,int $activeCount,int $activeBytes):array
 {
  $error=(int)($file['error']??UPLOAD_ERR_NO_FILE);if($error!==UPLOAD_ERR_OK)throw new \InvalidArgumentException($this->uploadError($error));
  $path=(string)($file['tmp_name']??'');$name=$this->filename((string)($file['name']??''));$bytes=(int)($file['size']??-1);
  if(!is_file($path)||$bytes<1||filesize($path)!==$bytes)throw new \InvalidArgumentException('The uploaded file could not be verified.');
  if(!CompanyNoticeAssetPolicy::permits($role,$name,$bytes,$activeCount,$activeBytes))throw new \InvalidArgumentException('The file type or combined upload limit is not allowed.');
  $ext=strtolower((string)pathinfo($name,PATHINFO_EXTENSION));$mime=(string)(new \finfo(FILEINFO_MIME_TYPE))->file($path);$dimensions=null;
  if(in_array($ext,['jpg','jpeg','png','webp'],true)){$allowed=['jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp']];if(!in_array($mime,$allowed[$ext],true))throw new \InvalidArgumentException('The image content does not match its filename.');$info=@getimagesize($path);if(!is_array($info)||($info[0]??0)<1||($info[1]??0)<1||$info[0]>12000||$info[1]>12000||$info[0]*$info[1]>40000000)throw new \InvalidArgumentException('The image dimensions are invalid or unsafe.');$dimensions=['width'=>(int)$info[0],'height'=>(int)$info[1]];}
  elseif($ext==='pdf'){if($mime!=='application/pdf'||file_get_contents($path,false,null,0,5)!=='%PDF-')throw new \InvalidArgumentException('The uploaded PDF is invalid.');}
  elseif($ext==='zip'){$this->zip->validate($path,$name,$bytes);$mime='application/zip';}
  elseif(in_array($ext,['docx','xlsx'],true)){$this->office($path,$ext);$mime=$ext==='docx'?'application/vnd.openxmlformats-officedocument.wordprocessingml.document':'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';}
  else throw new \InvalidArgumentException('The uploaded file type is not allowed.');
  $sha=hash_file('sha256',$path);if(!is_string($sha)||strlen($sha)!==64)throw new \RuntimeException('The upload checksum could not be calculated.');
  return ['path'=>$path,'original_filename'=>$name,'display_filename'=>$name,'mime_type'=>$mime,'byte_size'=>$bytes,'sha256'=>$sha,'role'=>$role,'dimensions'=>$dimensions,'type_label'=>$this->label($ext)];
 }
 private function office(string $path,string $extension):void{$zip=new \ZipArchive();if($zip->open($path)!==true)throw new \InvalidArgumentException('The Office document is malformed.');try{$required=$extension==='docx'?'word/document.xml':'xl/workbook.xml';if($zip->locateName('[Content_Types].xml')===false||$zip->locateName($required)===false)throw new \InvalidArgumentException('The Office document content is invalid.');foreach(['word/vbaProject.bin','xl/vbaProject.bin'] as $macro)if($zip->locateName($macro)!==false)throw new \InvalidArgumentException('Macro-enabled documents are not allowed.');}finally{$zip->close();}}
 private function filename(string $name):string{$name=trim(basename(str_replace('\\','/',$name)));if($name===''||mb_strlen($name)>255||preg_match('/[\r\n\x00-\x1F\x7F]/',$name))throw new \InvalidArgumentException('The upload filename is invalid.');return $name;}
 private function label(string $ext):string{return CompanyNoticePresentation::typeLabel('file.'.$ext);}
 private function uploadError(int $error):string{return match($error){UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE=>'The file exceeds the upload size limit.',UPLOAD_ERR_PARTIAL=>'The file upload was incomplete.',UPLOAD_ERR_NO_FILE=>'Choose one file to upload.',default=>'The file upload could not be completed.'};}
}
