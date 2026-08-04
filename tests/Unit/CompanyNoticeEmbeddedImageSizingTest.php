<?php
declare(strict_types=1);
use NpmGateway\Services\CompanyNoticeValidator;
use PHPUnit\Framework\TestCase;
final class CompanyNoticeEmbeddedImageSizingTest extends TestCase
{
 public function testApprovedWidthsSurviveWithoutAuthorStylesAndLegacyNormalizesSafely():void
 {
  $id=str_repeat('A',26);$validator=new CompanyNoticeValidator();$base=['title'=>'Image','message'=>'fallback','priority'=>'normal','requires_acknowledgment'=>'yes'];
  foreach([10,25,42,100] as $width){$data=$validator->validate($base+['rich_message_html'=>'<p><img src="gateway-storage:'.$id.'" data-storage-object-public-id="'.$id.'" data-gateway-image-width="'.$width.'" alt="Flyer" style="width:3px"></p>']);self::assertStringContainsString('data-gateway-image-width="'.$width.'"',$data['rich_message_html']);self::assertStringNotContainsString('style=',$data['rich_message_html']);}
  foreach(['small'=>25,'medium'=>50,'large'=>75,'full'=>100] as $size=>$width){$legacy=$validator->validate($base+['rich_message_html'=>'<p><img src="gateway-storage:'.$id.'" data-storage-object-public-id="'.$id.'" data-gateway-image-size="'.$size.'" alt="Flyer"></p>']);self::assertStringContainsString('data-gateway-image-width="'.$width.'"',$legacy['rich_message_html']);self::assertStringNotContainsString('data-gateway-image-size',$legacy['rich_message_html']);}
  $missing=$validator->validate($base+['rich_message_html'=>'<p><img src="gateway-storage:'.$id.'" data-storage-object-public-id="'.$id.'" alt="Flyer"></p>']);self::assertStringContainsString('data-gateway-image-width="50"',$missing['rich_message_html']);
  self::assertStringContainsString('data-gateway-image-align="left"',$missing['rich_message_html']);
  foreach(['left','center','right'] as $alignment){$aligned=$validator->validate($base+['rich_message_html'=>'<p><img src="gateway-storage:'.$id.'" data-storage-object-public-id="'.$id.'" data-gateway-image-width="25" data-gateway-image-align="'.$alignment.'" alt="Flyer"></p>']);self::assertStringContainsString('data-gateway-image-align="'.$alignment.'"',$aligned['rich_message_html']);}
 }
 public function testUnknownWidthsAndRemoteOrDataImagesAreRejected():void
 {
  $id=str_repeat('A',26);$validator=new CompanyNoticeValidator();$base=['title'=>'Image','message'=>'fallback','priority'=>'normal','requires_acknowledgment'=>'yes'];foreach(['9','101','42.5','42%','calc(100)'] as $width){try{$validator->validate($base+['rich_message_html'=>'<img src="gateway-storage:'.$id.'" data-storage-object-public-id="'.$id.'" data-gateway-image-width="'.$width.'" alt="Flyer">']);self::fail('Unsafe width accepted.');}catch(InvalidArgumentException){self::addToAssertionCount(1);}}foreach(['<img src="gateway-storage:'.$id.'" data-storage-object-public-id="'.$id.'" data-gateway-image-width="25" data-gateway-image-align="float:left" alt="Flyer">','<img src="https://example.test/image.png" alt="Remote">','<img src="data:image/png;base64,AAAA" alt="Data">'] as $html){try{$validator->validate($base+['rich_message_html'=>$html]);self::fail('Unsafe image accepted.');}catch(InvalidArgumentException){self::addToAssertionCount(1);}}
 }
 public function testReviewRecipientAndEmailMappingsAreDeclared():void
 {
  $root=dirname(__DIR__,2);$review=(string)file_get_contents($root.'/resources/views/company-notices/review.php');self::assertStringContainsString('/preview?compose_context=',$review);self::assertStringContainsString('renderImageWidths',$review);self::assertStringContainsString('gateway-notice-rich-body',$review);$show=(string)file_get_contents($root.'/resources/views/company-notices/show.php');self::assertStringContainsString('/storage/',$show);self::assertStringContainsString('renderImageWidths',$show);self::assertStringContainsString('gateway-notice-rich-body',$show);$email=(string)file_get_contents($root.'/app/Notifications/CompanyNoticeEmailSender.php');foreach(['round(640*$percent/100)','data-gateway-image-align','margin-left:auto;margin-right:auto','font-size:16px','line-height:1.6'] as $required)self::assertStringContainsString($required,$email);$sanitizer=new \NpmGateway\Services\RichTextSanitizer();$rendered=$sanitizer->renderImageWidths('<img data-gateway-image-width="42" style="width:1px">');self::assertStringContainsString('style="width:42%;max-width:100%;height:auto;"',$rendered);self::assertStringNotContainsString('1px',$rendered);
 }
}
