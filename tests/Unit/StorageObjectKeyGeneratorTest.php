<?php
declare(strict_types=1);
use NpmGateway\Services\StorageObjectKeyGenerator;
use PHPUnit\Framework\TestCase;
final class StorageObjectKeyGeneratorTest extends TestCase
{
 public function testApprovedPrefixesUseUtcTimestampRandomTokenAndSafeFilename():void{$generator=new StorageObjectKeyGenerator();$at=new DateTimeImmutable('2026-08-02 12:34:56',new DateTimeZone('America/New_York'));foreach(['company_notices/attachments/','company_notices/images/','company_notices/test/'] as $prefix){$key=$generator->generate($prefix,' Unsafe policy (final).PDF ',$at);self::assertMatchesRegularExpression('#^'.preg_quote($prefix,'#').'20260802_163456_[A-F0-9]{8}_Unsafe_policy_final\.pdf$#',$key);}}
 public function testUnsafePrefixIsRejected():void{$this->expectException(InvalidArgumentException::class);(new StorageObjectKeyGenerator())->generate('../production/','file.txt');}
}
