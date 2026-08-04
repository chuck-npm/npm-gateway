<?php
declare(strict_types=1);
use NpmGateway\Console\UploadPreflightCommand;
use PHPUnit\Framework\TestCase;
final class UploadPreflightCommandTest extends TestCase
{
 public function testReadyRuntimeRequiresMultipartOverhead():void{$values=['upload_max_filesize'=>'100M','post_max_size'=>'101M','max_file_uploads'=>'1','max_input_time'=>'120','max_execution_time'=>'120','memory_limit'=>'512M'];$result=UploadPreflightCommand::run(static fn(string $key)=>$values[$key]);self::assertSame(0,$result['exit_code']);self::assertStringContainsString('104857600 bytes',$result['stdout']);}
 public function testEqualPostLimitIsBlockedSafely():void{$values=['upload_max_filesize'=>'100M','post_max_size'=>'100M','max_file_uploads'=>'20','max_input_time'=>'-1','max_execution_time'=>'0','memory_limit'=>'512M'];$result=UploadPreflightCommand::run(static fn(string $key)=>$values[$key]);self::assertSame(1,$result['exit_code']);self::assertStringNotContainsString('php.ini',$result['stdout']);self::assertStringContainsString('multipart upload',$result['stderr']);}
}
