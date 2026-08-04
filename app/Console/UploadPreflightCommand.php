<?php
declare(strict_types=1);
namespace NpmGateway\Console;
use NpmGateway\Services\CompanyNoticeAssetPolicy;
final class UploadPreflightCommand
{
 /** @param callable(string):string|false|null $reader */
 public static function run(?callable $reader=null):array
 {
  $reader??=static fn(string $key)=>ini_get($key);$keys=['upload_max_filesize','post_max_size','max_file_uploads','max_input_time','max_execution_time','memory_limit'];$values=[];foreach($keys as $key)$values[$key]=(string)$reader($key);$upload=self::bytes($values['upload_max_filesize']);$post=self::bytes($values['post_max_size']);$ok=$upload>=CompanyNoticeAssetPolicy::MAX_OBJECT_BYTES&&$post>CompanyNoticeAssetPolicy::MAX_OBJECT_BYTES&&(int)$values['max_file_uploads']>=1;$lines=['Company Notice upload preflight: '.($ok?'ready':'blocked'),'Runtime SAPI: '.PHP_SAPI];foreach($values as $key=>$value)$lines[]=$key.': '.($value===''?'unavailable':$value);$lines[]='Required object limit: 104857600 bytes (100 MiB)';$lines[]='post_max_size must exceed 100 MiB for multipart overhead.';$lines[]='IIS request filtering: verify in the deployed web runtime; CLI cannot inspect effective IIS limits.';return ['exit_code'=>$ok?0:1,'stdout'=>implode("\n",$lines)."\n",'stderr'=>$ok?'':"Effective PHP upload limits do not support a 100 MiB multipart upload.\n"];
 }
 private static function bytes(string $value):int{$value=trim($value);if($value===''||$value==='-1')return PHP_INT_MAX;if(!preg_match('/^([0-9]+)([KMG]?)$/i',$value,$m))return 0;$bytes=(int)$m[1];return $bytes*match(strtoupper($m[2])){'K'=>1024,'M'=>1048576,'G'=>1073741824,default=>1};}
}
