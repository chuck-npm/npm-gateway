<?php
declare(strict_types=1);
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\TestCase;
final class EmployeeEmergencyContactRenderingTest extends TestCase
{
 public function testEmptyStateAlwaysRendersCompleteCreateForm():void{$html=$this->render([]);$this->assertForm($html);foreach(['first_name','last_name','relationship','primary_phone','alternate_phone'] as $field)self::assertMatchesRegularExpression('/name="'.$field.'"[^>]*value=""/',$html);}
 public function testExistingStateRendersSameFormPopulated():void{$html=$this->render(['first_name'=>'Fictional','last_name'=>'Contact','relationship'=>'Friend','primary_phone'=>'(570) 555-0101','alternate_phone'=>'(570) 555-0102']);$this->assertForm($html);foreach(['Fictional','Contact','Friend','(570) 555-0101','(570) 555-0102'] as $value)self::assertStringContainsString('value="'.htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'"',$html);}
 private function assertForm(string $html):void{foreach(['First Name','Last Name','Relationship','Primary Phone','Alternate Phone (Optional)','Cancel','Save Emergency Contact'] as $text)self::assertStringContainsString($text,$html);self::assertSame(2,substr_count($html,'data-phone-mask'));self::assertSame(2,substr_count($html,'inputmode="tel"'));self::assertSame(2,substr_count($html,'autocomplete="tel"'));self::assertSame(2,substr_count($html,'maxlength="14"'));self::assertStringContainsString('data-processing-form',$html);self::assertStringNotContainsString('<form method="post" action="/my/emergency-contact" data-processing-overlay',$html);}
 private function render(array $input):string{$root=dirname(__DIR__,2);$user=new AuthenticatedUser(1,1,str_repeat('U',26),str_repeat('E',26),'tester','Test User');$csrfToken='csrf';$logoutCsrfToken='logout';$errors=[];$success='';$navbarCorporateItems=[];ob_start();require $root.'/resources/views/my/emergency-contact.php';return (string)ob_get_clean();}
}
