<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class RmCorrectionResponseContextTest extends TestCase
{
 public function testResponsePageShowsAuthoritativeReadOnlyBusinessContext():void
 {
  $view=$this->view();foreach(['Request Context','Property','Lot / Address','Tenant','Correction Request','Information Requested',"request['property_name']","request['lot_address']","request['tenant_name']","request['correction_request']",'informationRequested','nl2br($e(']as$required)self::assertStringContainsString($required,$view);foreach(['name="property"','name="property_id"','name="lot_address"','name="tenant_name"','name="correction_request"','name="reviewer_comments"','name="information_requested"']as$editable)self::assertStringNotContainsString($editable,$view);self::assertSame(1,substr_count($view,'name="additional_information"'));self::assertSame(1,substr_count($view,'name="_token"'));
 }
 public function testLatestInformationRequestComesFromAuthoritativeHistory():void
 {
  $controller=$this->controller();self::assertStringContainsString('$this->query->managerDetail($public,$x->propertyId)',$controller);self::assertStringContainsString("if(\$request['status']!=='more_information_needed')",$controller);self::assertStringContainsString('$this->latestInformationRequested($request[\'history\'])',$controller);self::assertStringContainsString('foreach(array_reverse($history)as$event)',$controller);self::assertStringContainsString("event_type']??null)==='more_information_needed'",$controller);self::assertStringContainsString("event['comments']",$controller);foreach(['query[','post[\'reviewer','information_requested']as$untrusted)self::assertStringNotContainsString($untrusted,substr($controller,strpos($controller,'private function latestInformationRequested')));
 }
 public function testCancelIsPropertyScopedPublicIdNavigationWithoutMutation():void
 {
  $view=$this->view();self::assertStringContainsString('>Cancel</a>',$view);self::assertStringContainsString('$context->propertySlug',$view);self::assertStringContainsString("request['public_id']",$view);self::assertStringNotContainsString("request['id']",$view);self::assertSame(1,substr_count($view,'<form '));self::assertStringNotContainsString('name="cancel"',$view);self::assertStringNotContainsString('<button class="btn btn-secondary"',$view);
 }
 public function testResponsePostStillAcceptsOnlyAdditionalInformationAndRechecksState():void
 {
  $controller=$this->controller();$service=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/RmCorrectionService.php');$validator=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/RmCorrectionValidator.php');self::assertStringContainsString("['additional_information'=>",$validator);foreach(['property_id','lot_address','tenant_name','correction_request','reviewer_comments']as$field)self::assertStringNotContainsString("['{$field}'=>",substr($validator,strpos($validator,'public function response')));self::assertStringContainsString("managerDetail(\$public,\$c->propertyId,true)",$service);self::assertStringContainsString("status']!=='more_information_needed'",$service);self::assertStringContainsString("'manager_responded'",$service);self::assertStringContainsString("respond(\$x,\$public,\$r->post)",$controller);
 }
 public function testNoInlinePresentationOrScriptWasIntroduced():void{$view=$this->view();self::assertStringNotContainsString('<style',$view);self::assertStringNotContainsString('style=',$view);self::assertStringNotContainsString('<script',$view);}
 private function view():string{return(string)file_get_contents(dirname(__DIR__,2).'/resources/views/community-actions/rm-corrections/respond.php');}
 private function controller():string{return(string)file_get_contents(dirname(__DIR__,2).'/app/Http/Controllers/RmCorrectionController.php');}
}
