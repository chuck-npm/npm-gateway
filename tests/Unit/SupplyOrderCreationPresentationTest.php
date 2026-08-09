<?php declare(strict_types=1);namespace Tests\Unit;use PHPUnit\Framework\TestCase;
final class SupplyOrderCreationPresentationTest extends TestCase
{
 private string$root;private string$css;private string$view;private string$javascript;
 protected function setUp():void{$this->root=dirname(__DIR__,2);$this->css=(string)file_get_contents($this->root.'/public/assets/css/gateway.css');$this->view=(string)file_get_contents($this->root.'/resources/views/community-actions/supply-orders/create.php');$this->javascript=(string)file_get_contents($this->root.'/public/assets/js/supply-order-editor.js');}
 public function testInitializedSupplyOrderEditableAreaHasScopedReadableTypographyAndPreservedHeight():void
 {
  self::assertStringContainsString('class="gateway-supply-order-form__editor" id="supply-order-editor"',$this->view);
  self::assertMatchesRegularExpression('/\.gateway-supply-order-form #supply-order-editor\.gateway-supply-order-form__editor\.ql-container > \.ql-editor\[contenteditable="true"\]\s*\{[^}]*min-height:17rem;[^}]*font-size:1\.05rem;[^}]*line-height:1\.6;[^}]*overflow-wrap:anywhere;/',$this->css);
  self::assertDoesNotMatchRegularExpression('/(^|[},]\s*)\.ql-editor(?:\s|\{|\[|:)/m',$this->css);
 }
 public function testToolbarAndEditorBehaviorRemainRestrictedAndSynchronized():void
 {
  foreach(["['bold','italic','underline']","[{list:'ordered'},{list:'bullet'}]","[{indent:'-1'},{indent:'+1'}]","['link','clean']","formats:['bold','italic','underline','list','indent','link']"]as$required)self::assertStringContainsString($required,$this->javascript);
  foreach(["'image'","'video'","'font'","'size'","'color'","'header'",'iframe']as$forbidden)self::assertStringNotContainsString($forbidden,$this->javascript);
  foreach(["quill.on('text-change',sync)","form.addEventListener('submit'",'synchronizeSupplyRequest(quill,field)']as$required)self::assertStringContainsString($required,$this->javascript);
 }
 public function testSharedStylesheetUsesModificationTimeCacheVersionAfterQuillCss():void
 {
  $header=(string)file_get_contents($this->root.'/resources/views/components/header.php');
  self::assertStringContainsString('filemtime($gatewayCssPath)',$header);
  self::assertStringContainsString('/assets/css/gateway.css?v=',$header);
  self::assertLessThan(strpos($header,'/assets/css/gateway.css?v='),strpos($header,'quill.snow.css'));
 }
}
