<?php
declare(strict_types=1);
use NpmGateway\Services\EmployeeCreationSubmissionStore;
use NpmGateway\Support\PublicIdGenerator;
use PHPUnit\Framework\TestCase;
final class EmployeeCreationSubmissionStoreTest extends TestCase
{
    public function testTokenIsOpaqueOwnerBoundAndClaimsOnlyOnce():void{$session=[];$store=new EmployeeCreationSubmissionStore($session,new PublicIdGenerator());$token=$store->create(7);self::assertMatchesRegularExpression('/^[A-Z0-9]{26}$/',$token);self::assertSame('unavailable',$store->begin($token,8)['status']);self::assertSame('processing_started',$store->begin($token,7)['status']);self::assertSame('processing',$store->begin($token,7)['status']);}
    public function testValidationRestoreAllowsCorrectionButCompletionDoesNot():void{$session=[];$store=new EmployeeCreationSubmissionStore($session,new PublicIdGenerator());$token=$store->create(7);$store->begin($token,7);$store->restore($token,7);self::assertSame('processing_started',$store->begin($token,7)['status']);$store->committed($token,str_repeat('E',26));self::assertSame('committed',$store->begin($token,7)['status']);self::assertSame(['employee_public_id'=>str_repeat('E',26)],$store->begin($token,7)['result']);$store->complete($token,str_repeat('E',26));self::assertSame('completed',$store->begin($token,7)['status']);self::assertArrayNotHasKey('password',$session['employee_creation_submissions'][$token]);}
    public function testExpiredAndMalformedTokensAreRejected():void{$now=1000;$session=[];$store=new EmployeeCreationSubmissionStore($session,new PublicIdGenerator(),function()use(&$now){return $now;});$token=$store->create(7);$now=2801;self::assertSame('expired',$store->begin($token,7)['status']);self::assertSame('malformed',$store->begin('not valid',7)['status']);}
}
