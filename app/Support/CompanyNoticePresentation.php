<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class CompanyNoticePresentation
{
 public static function typeLabel(array|string $asset):string{$filename=is_array($asset)?(string)($asset['display_filename']??''):$asset;$extension=strtolower((string)pathinfo($filename,PATHINFO_EXTENSION));return match($extension){'pdf'=>'PDF','docx'=>'DOCX','xlsx'=>'XLSX','zip'=>'ZIP','jpg'=>'JPG','jpeg'=>'JPEG','png'=>'PNG','webp'=>'WebP',default=>'File'};}
 public static function fileSize(int $bytes):string{if($bytes<1024)return $bytes.' B';if($bytes<1048576)return rtrim(rtrim(number_format($bytes/1024,1),'0'),'.').' KiB';return rtrim(rtrim(number_format($bytes/1048576,1),'0'),'.').' MiB';}
 public static function attachmentHeading(int $count):string{return 'Attachments ('.$count.')';}
 public static function publishedAt(string $value):string{try{return (new \DateTimeImmutable($value))->format('F j, Y \a\t g:i A');}catch(\Throwable){return $value;}}
 public static function paperclip():string{return '<i class="fa-solid fa-paperclip" aria-hidden="true"></i>';}
}
