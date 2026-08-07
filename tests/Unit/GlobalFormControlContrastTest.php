<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class GlobalFormControlContrastTest extends TestCase
{
 private string $css;
 protected function setUp():void{$this->css=(string)file_get_contents(dirname(__DIR__,2).'/public/assets/css/gateway.css');}
 public function testOneAuthoritativeMediumSlateTokenStylesStandardControls():void
 {
  self::assertSame(1,substr_count($this->css,'--gateway-control-border: #838d9b;'));self::assertStringContainsString(".form-control,\n.form-select {\n    border-color: var(--gateway-control-border);",$this->css);self::assertStringContainsString('border:1px solid var(--gateway-control-border)',$this->css);self::assertStringNotContainsString('.gateway-rm-correction',$this->css);self::assertLessThan(0xD8,hexdec('83'));
 }
 public function testFocusValidationAndDisabledStatesOverrideIdlePresentation():void
 {
  foreach(['.form-control:focus,','.form-select:focus {','border-color: var(--gateway-blue);','box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.2);','.form-control.is-invalid,','.form-select.is-invalid,','border-color: var(--bs-form-invalid-border-color);','.form-control.is-valid,','.form-select.is-valid,','border-color: var(--bs-form-valid-border-color);','.form-control:disabled,','.form-select:disabled,','.form-control[readonly] {','background-color: var(--gateway-control-disabled-bg);','border-color: var(--gateway-control-border-disabled);','opacity: 0.75;'] as $rule)self::assertStringContainsString($rule,$this->css);
  self::assertGreaterThan(strpos($this->css,'border-color: var(--gateway-control-border);'),strpos($this->css,'.form-control:focus,'));self::assertGreaterThan(strpos($this->css,'.form-control:focus,'),strpos($this->css,'.form-control.is-invalid,'));self::assertGreaterThan(strpos($this->css,'.form-control.is-valid,'),strpos($this->css,'.form-control:disabled,'));
 }
 public function testFileSelectDateTextareaInheritWithoutNativeReplacement():void
 {
  self::assertStringNotContainsString('appearance: none',$this->css);self::assertStringNotContainsString('::-webkit-calendar-picker-indicator',$this->css);self::assertStringNotContainsString('::file-selector-button',$this->css);self::assertStringNotContainsString('input[type="date"]',$this->css);self::assertStringNotContainsString('textarea {',$this->css);
 }
 public function testCheckboxAndRadioStandardsRemainUnchanged():void
 {
  self::assertStringContainsString('--gateway-control-border-strong: #53657a;',$this->css);self::assertStringContainsString('.form-check-input[type="checkbox"]:not(:checked):not(:disabled):not(.is-invalid)',$this->css);self::assertStringContainsString('border-color:var(--gateway-control-border-strong)',$this->css);self::assertStringNotContainsString('.form-check-input[type="radio"]',$this->css);
 }
 public function testViewsUseSharedClassesWithoutInlineControlOverrides():void
 {
  $root=dirname(__DIR__,2);$views='';foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/resources/views'))as$file)if($file->isFile()&&$file->getExtension()==='php')$views.=(string)file_get_contents($file->getPathname());self::assertStringNotContainsString('style="border',$views);self::assertStringContainsString('class="form-control',$views);self::assertStringContainsString('class="form-select',$views);
 }
}
