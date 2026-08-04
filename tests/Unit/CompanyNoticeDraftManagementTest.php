<?php
declare(strict_types=1);
use NpmGateway\Services\CompanyNoticeComposeStore;
use NpmGateway\Services\CompanyNoticeReviewStore;
use NpmGateway\Support\FlashSession;
use NpmGateway\Support\PublicIdGenerator;
use PHPUnit\Framework\TestCase;
final class CompanyNoticeDraftManagementTest extends TestCase
{
 public function testDiscardRemovesOnlyOwnedComposeAndCreatesANewContext():void
 {
  $session=[];$ids=new PublicIdGenerator();$store=new CompanyNoticeComposeStore($session,$ids,static fn():int=>1000);$first=$store->active(7);$other=$store->active(8);self::assertTrue($store->discard($first['id'],7));self::assertFalse($store->discard($first['id'],7));self::assertNotSame($first['id'],$store->active(7)['id']);self::assertSame($other['id'],$store->current(8)['id']);
 }
 public function testExpiredComposeIsUnavailableAndReplaced():void
 {
  $now=1000;$session=[];$store=new CompanyNoticeComposeStore($session,new PublicIdGenerator(),function()use(&$now):int{return $now;});$old=$store->active(7);$now+=86401;self::assertNull($store->current(7));self::assertFalse($store->discard($old['id'],7));self::assertNotSame($old['id'],$store->active(7)['id']);
 }
 public function testDiscardInvalidatesEveryReviewForComposeButNotOtherOrPublishedReviews():void
 {
  $session=[];$store=new CompanyNoticeReviewStore($session,new PublicIdGenerator(),static fn():int=>1000);$compose=str_repeat('C',26);$first=$store->create(7,['compose_context'=>$compose,'title'=>'One']);$second=$store->create(7,['compose_context'=>$compose,'title'=>'Two']);$other=$store->create(8,['compose_context'=>$compose,'title'=>'Other owner']);$published=$store->create(7,['compose_context'=>str_repeat('P',26),'title'=>'Published']);$store->complete($published,str_repeat('N',26));self::assertSame(2,$store->discardCompose(7,$compose));self::assertSame('unavailable',$store->resolve($first,7)['status']);self::assertSame('unavailable',$store->resolve($second,7)['status']);self::assertSame('created',$store->resolve($other,8)['status']);self::assertSame('published',$store->resolve($published,7)['status']);
 }
 public function testExpiredReviewForComposeIsAlsoRemovedAndFlashDraftStateCanBeCleared():void
 {
  $now=1000;$session=[];$reviews=new CompanyNoticeReviewStore($session,new PublicIdGenerator(),function()use(&$now):int{return $now;});$compose=str_repeat('C',26);$token=$reviews->create(7,['compose_context'=>$compose]);$now=2000;self::assertSame('expired',$reviews->resolve($token,7)['status']);self::assertSame(1,$reviews->discardCompose(7,$compose));$flash=new FlashSession($session);$flash->put('company_notice_input',['title'=>'Draft']);$flash->forget('company_notice_input');self::assertNull($flash->pull('company_notice_input'));
 }
 public function testRouteViewsDialogAndFailureContractArePresent():void
 {
  $root=dirname(__DIR__,2);$routes=require $root.'/routes/web.php';self::assertSame(['POST'],$routes['/company-notices/discard']['methods']);$create=(string)file_get_contents($root.'/resources/views/company-notices/create.php');$review=(string)file_get_contents($root.'/resources/views/company-notices/review.php');$dialog=(string)file_get_contents($root.'/resources/views/company-notices/_discard-dialog.php');foreach([$create,$review] as $view)self::assertStringContainsString('data-discard-open',$view);foreach(['Discard this draft?','All uploaded attachments and embedded images that have not been published will be permanently removed.','This action cannot be undone.','aria-modal="true"','data-discard-cancel'] as $required)self::assertStringContainsString($required,$dialog);$controller=(string)file_get_contents($root.'/app/Http/Controllers/CompanyNoticeController.php');self::assertStringContainsString('Draft could not be discarded safely.',$controller);self::assertStringContainsString('This draft is no longer available.',$controller);self::assertStringNotContainsString('npm_gateway_session',$controller.$dialog);
 }
 public function testDiscardServiceUsesExistingRemovalLifecycleAndSafeAuditMetadata():void
 {
  $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/CompanyNoticeDraftDiscardService.php');self::assertStringContainsString('$this->assets->remove(',$source);self::assertStringContainsString('company_notice.draft_discarded',$source);foreach(['publisher_public_id','compose_context_id','attachment_count','embedded_image_count'] as $key)self::assertStringContainsString($key,$source);foreach(['object_key','provider_container','review_token','message_body','display_filename','adapter->delete'] as $forbidden)self::assertStringNotContainsString($forbidden,$source);
 }
}
