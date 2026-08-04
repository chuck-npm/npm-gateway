<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class CompanyNoticeAssetPolicy
{
 public const MAX_ASSETS=10;
 public const MAX_OBJECT_BYTES=104857600;
 public const MAX_TOTAL_BYTES=1048576000;
 public const ATTACHMENT_EXTENSIONS=['pdf','docx','xlsx','zip','jpg','jpeg','png','webp'];
 public const EMBEDDED_IMAGE_EXTENSIONS=['jpg','jpeg','png','webp'];
 public static function permits(string $role,string $filename,int $bytes,int $activeCount,int $activeBytes):bool
 {
  $extension=strtolower((string)pathinfo($filename,PATHINFO_EXTENSION));$allowed=$role==='embedded_image'?self::EMBEDDED_IMAGE_EXTENSIONS:($role==='attachment'?self::ATTACHMENT_EXTENSIONS:[]);
  return in_array($extension,$allowed,true)&&$bytes>0&&$bytes<=self::MAX_OBJECT_BYTES&&$activeCount<self::MAX_ASSETS&&$activeBytes+$bytes<=self::MAX_TOTAL_BYTES;
 }
}
