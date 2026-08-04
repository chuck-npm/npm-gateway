<?php
declare(strict_types=1);
use NpmGateway\Services\CompanyNoticeAssetPolicy as Policy;
use PHPUnit\Framework\TestCase;
final class CompanyNoticeAssetPolicyTest extends TestCase
{
 public function testApprovedByteAccurateBoundaries():void{self::assertSame(104857600,Policy::MAX_OBJECT_BYTES);self::assertSame(1048576000,Policy::MAX_TOTAL_BYTES);self::assertTrue(Policy::permits('attachment','archive.zip',Policy::MAX_OBJECT_BYTES,9,Policy::MAX_TOTAL_BYTES-Policy::MAX_OBJECT_BYTES));self::assertFalse(Policy::permits('attachment','archive.zip',Policy::MAX_OBJECT_BYTES+1,0,0));self::assertTrue(Policy::permits('attachment','file.pdf',1,9,Policy::MAX_TOTAL_BYTES-1));self::assertFalse(Policy::permits('attachment','file.pdf',1,10,0));self::assertFalse(Policy::permits('attachment','file.pdf',1,9,Policy::MAX_TOTAL_BYTES));}
 public function testRoleAllowListsSeparateAttachmentsAndEmbeddedImages():void{foreach(['pdf','docx','xlsx','zip','jpg','jpeg','png','webp'] as $extension)self::assertTrue(Policy::permits('attachment','file.'.$extension,1,0,0));foreach(['jpg','jpeg','png','webp'] as $extension)self::assertTrue(Policy::permits('embedded_image','file.'.$extension,1,0,0));foreach(['zip','svg','docm','exe','rar','7z','tar','gz'] as $extension)self::assertFalse(Policy::permits('embedded_image','file.'.$extension,1,0,0));}
}
