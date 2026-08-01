<?php
declare(strict_types=1);
use NpmGateway\Support\PhoneFormatter;
use PHPUnit\Framework\TestCase;
final class PhoneFormatterTest extends TestCase
{
    public function testStoredUsPhoneRepresentationsUseOneDisplayFormat():void
    {
        $formatter=new PhoneFormatter();
        self::assertSame('(570) 213-3312',$formatter->format('5702133312'));
        self::assertSame('(706) 329-2986',$formatter->format('+17063292986'));
    }
}
