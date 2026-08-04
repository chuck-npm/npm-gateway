<?php
declare(strict_types=1);
namespace Tests\Unit;
use NpmGateway\Notifications\CompanyAnnouncementEmailRenderer;
use NpmGateway\Notifications\CompanyNoticeEmailSender;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\Support\CompanyNoticePresentation;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

final class CompanyNoticePresentationTest extends TestCase
{
 public function testCentralPresentationUsesConciseTypesSizesAndAttachmentCounts():void
 {
  foreach(['policy.pdf'=>'PDF','report.docx'=>'DOCX','sheet.xlsx'=>'XLSX','forms.zip'=>'ZIP','photo.jpg'=>'JPG','photo.jpeg'=>'JPEG','plan.png'=>'PNG','pool.webp'=>'WebP'] as $file=>$label)self::assertSame($label,CompanyNoticePresentation::typeLabel($file));
  self::assertSame('2.4 MiB',CompanyNoticePresentation::fileSize(2516582));self::assertSame('Attachments (2)',CompanyNoticePresentation::attachmentHeading(2));
 }
 public function testCompanyNoticeViewsUseCountedAccessibleAttachmentHeadingsWithoutAssetsTerm():void
 {
  $root=dirname(__DIR__,2);foreach(['resources/views/company-notices/create.php','resources/views/company-notices/review.php','resources/views/company-notices/show.php','resources/views/notifications/show.php'] as $file){$source=(string)file_get_contents($root.'/'.$file);self::assertStringNotContainsString('>Assets<',$source);self::assertStringNotContainsString('fa-paperclip',$source);self::assertStringNotContainsString('fa-solid',$source);self::assertStringContainsString("components/icon-paperclip.php",$source);}
  $icon=(string)file_get_contents($root.'/resources/views/components/icon-paperclip.php');foreach(['<svg','width="1em"','height="1em"','stroke="currentColor"','aria-hidden="true"','focusable="false"'] as $required)self::assertStringContainsString($required,$icon);foreach(['<script','onload=','onclick=','onerror=','href=','http://','https://','font-family'] as $forbidden)self::assertStringNotContainsString($forbidden,$icon);
  $review=(string)file_get_contents($root.'/resources/views/company-notices/review.php');self::assertStringContainsString("asset_role']==='attachment'",$review);self::assertStringContainsString('attachmentHeading(count($ordinary))',$review);
  $recipient=(string)file_get_contents($root.'/resources/views/notifications/show.php');self::assertSame(1,substr_count($recipient,'<dt>Published by</dt>'));self::assertStringContainsString('I Have Read This Notice',$recipient);
 }
 public function testCompanyNoticeEmailHasMetadataAttachmentsAndRestrainedFooter():void
 {
  $mail=new class(true) extends PHPMailer{public function send():bool{return true;}};$config=new HrEmployeeNotificationConfig('smtp.example.test',587,'fake','fake','tls','no-reply@example.test','NPM Gateway',[],'testing');$sender=new CompanyNoticeEmailSender($config,new CompanyAnnouncementEmailRenderer(),static fn():PHPMailer=>$mail,null,'https://gateway.example.test');
  $assets=[['public_id'=>str_repeat('P',26),'display_filename'=>'Updated Rent Policy.pdf','mime_type'=>'application/pdf','byte_size'=>2516582,'asset_role'=>'attachment']];
  self::assertTrue($sender->send('recipient@example.test',['title'=>'Notice','message'=>'Body','rich_message_html'=>'<p>Body</p>','published_by'=>'Chuck Lundquist','published_at'=>'August 2, 2026 at 8:08 PM','priority'=>'normal','assets'=>$assets]));
  foreach(['Published by','Chuck Lundquist','Published','August 2, 2026 at 8:08 PM','Priority','Normal','<h2 style="margin:26px 0 14px;font-family:Arial,sans-serif;font-size:20px;">Attachments</h2>','Updated Rent Policy.pdf','PDF · 2.4 MiB','Download from NPM Gateway','NPM Gateway — Internal company communication'] as $required)self::assertStringContainsString($required,$mail->Body);
  foreach(['ATTACHMENTS','Updated Rent Policy.pdf','PDF · 2.4 MiB','https://gateway.example.test/storage/'.str_repeat('P',26)] as $required)self::assertStringContainsString($required,$mail->AltBody);
  foreach(['wasabisys.com','provider_container','object_key','fa-paperclip','fontawesome'] as $forbidden){self::assertStringNotContainsString($forbidden,$mail->Body);self::assertStringNotContainsString($forbidden,$mail->AltBody);}
  foreach(['font-size:16px','line-height:1.6','margin:0 0 11px'] as $typography)self::assertStringContainsString($typography,$mail->Body);
  self::assertSame(0,substr_count($mail->Body,'Published by Chuck Lundquist'));self::assertCount(0,$mail->getAttachments());
 }
}
