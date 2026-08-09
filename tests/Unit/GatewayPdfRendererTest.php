<?php declare(strict_types=1);namespace Tests\Unit;
use NpmGateway\Services\Pdf\DompdfGatewayPdfRenderer;use NpmGateway\ValueObjects\GatewayPdfDocument;use PHPUnit\Framework\TestCase;
final class GatewayPdfRendererTest extends TestCase
{
 private string $root;
 protected function setUp():void{$this->root=dirname(__DIR__,2);}
 public function testSecureOptionsDisableRemoteResourcesAndJavascript():void{$renderer=new DompdfGatewayPdfRenderer($this->root.'/resources/views/pdf');$options=$renderer->options();self::assertFalse($options->getIsRemoteEnabled());self::assertFalse($options->getIsJavascriptEnabled());self::assertSame('DejaVu Sans',$options->getDefaultFont());self::assertContains(realpath($this->root.'/resources/views/pdf'),array_map('realpath',(array)$options->getChroot()));}
 public function testControlledUnicodeHtmlRendersValidPdfInMemory():void{$renderer=new DompdfGatewayPdfRenderer($this->root.'/resources/views/pdf');$before=glob($this->root.'/resources/views/pdf/*.pdf')?:[];$bytes=$renderer->render(new GatewayPdfDocument('Unicode report','<!doctype html><html><head><meta charset="utf-8"></head><body style="font-family: DejaVu Sans">O’Brien — “Résumé” • Café $19.95<ul><li>Finding</li></ul></body></html>'));self::assertStringStartsWith('%PDF-',$bytes);self::assertGreaterThan(500,strlen($bytes));self::assertSame($before,glob($this->root.'/resources/views/pdf/*.pdf')?:[]);}
 public function testDocumentRejectsUnsupportedConfiguration():void{$this->expectException(\InvalidArgumentException::class);new GatewayPdfDocument('Report','<p>Safe</p>','poster');}
}
