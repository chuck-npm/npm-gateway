<?php
declare(strict_types=1);
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;

final class CompanyNoticeUploadUiTest extends TestCase
{
 public function testComposeViewContainsAccessibleGatewayUploadPanel():void
 {
  $view=(string)file_get_contents(dirname(__DIR__,2).'/resources/views/company-notices/create.php');
  foreach(['Drag files here to upload','Choose Files','No files uploaded yet.','0 files selected (0 of 10)','0 B of 1,000 MiB used','aria-live="polite"','aria-atomic="true"','<progress','gateway-status--success','aria-label="Remove'] as $required)self::assertStringContainsString($required,$view.(string)file_get_contents(dirname(__DIR__,2).'/public/assets/js/company-notice-editor.js'));
  self::assertStringNotContainsString('class="form-control" id="company-notice-attachments"',$view);
 }

 public function testEditorHelperAndAttachmentsRetainAccessibleBlockOrder():void
 {
  $view=(string)file_get_contents(dirname(__DIR__,2).'/resources/views/company-notices/create.php');
  $editor=strpos($view,'id="company-notice-editor"');$helper=strpos($view,'id="company-notice-message-help"');$attachments=strpos($view,'id="notice-attachments-heading"');
  self::assertIsInt($editor);self::assertIsInt($helper);self::assertIsInt($attachments);self::assertLessThan($helper,$editor);self::assertLessThan($attachments,$helper);
  self::assertStringContainsString('class="form-text gateway-notice-editor-help"',$view);
  self::assertStringContainsString('aria-describedby="company-notice-message-help"',$view);
  self::assertStringNotContainsString('<style',$view);self::assertStringNotContainsString('<script',$view);self::assertStringNotContainsString(' style=',$view);
 }

 public function testCompanyNoticeEditorStylesAreScopedComfortableAndResponsive():void
 {
  $css=(string)file_get_contents(dirname(__DIR__,2).'/public/assets/css/gateway.css');
  self::assertMatchesRegularExpression('/\.gateway-company-notice-editor \.ql-container\s*\{[^}]*height:auto;[^}]*overflow:visible;/',$css);
  self::assertMatchesRegularExpression('/\.gateway-company-notice-editor \.ql-container\.ql-snow > \.ql-editor\[contenteditable="true"\]\s*\{[^}]*height:auto;[^}]*min-height:20rem;[^}]*padding:(1[2-6])px;[^}]*overflow-y:auto;/',$css);
  self::assertStringContainsString('.gateway-notice-editor-help { margin-top:.5rem;margin-bottom:1rem; }',$css);
  self::assertStringContainsString('@media (max-width:575.98px)',$css);
  self::assertDoesNotMatchRegularExpression('/(?:gateway-notice-editor-help|gateway-notice-attachments)[^{]*\{[^}]*position\s*:\s*absolute/i',$css);
  self::assertStringContainsString('gateway-upload-panel',$css);
 }

 public function testQuillWrapperContainsOnlyEditorAndFallbackBeforeHelper():void
 {
  $view=(string)file_get_contents(dirname(__DIR__,2).'/resources/views/company-notices/create.php');
  $wrapperStart=strpos($view,'<div class="gateway-company-notice-editor">');$editor=strpos($view,'id="company-notice-editor"');$fallback=strpos($view,'id="company-notice-message"');
  $wrapperEnd=strpos($view,"     </div>\n     <div class=\"form-text gateway-notice-editor-help\"");
  $helper=strpos($view,'id="company-notice-message-help"');$attachments=strpos($view,'id="notice-attachments-heading"');
  self::assertIsInt($wrapperStart);self::assertIsInt($editor);self::assertIsInt($fallback);self::assertIsInt($wrapperEnd);self::assertIsInt($helper);self::assertIsInt($attachments);
  self::assertLessThan($editor,$wrapperStart);self::assertLessThan($fallback,$editor);self::assertLessThan($wrapperEnd,$fallback);self::assertLessThan($helper,$wrapperEnd);self::assertLessThan($attachments,$helper);
  self::assertStringContainsString('.gateway-company-notice-editor .gateway-notice-message[hidden] { display:none; }',(string)file_get_contents(dirname(__DIR__,2).'/public/assets/css/gateway.css'));
 }
}
