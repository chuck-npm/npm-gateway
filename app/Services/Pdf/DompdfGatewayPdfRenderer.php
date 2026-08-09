<?php declare(strict_types=1);namespace NpmGateway\Services\Pdf;
use Dompdf\Dompdf;use Dompdf\Options;use NpmGateway\Contracts\GatewayPdfRendererInterface;use NpmGateway\ValueObjects\GatewayPdfDocument;
final readonly class DompdfGatewayPdfRenderer implements GatewayPdfRendererInterface
{
 public function __construct(private string $chroot)
 {
  if(!is_dir($chroot))throw new \InvalidArgumentException('The PDF resource root is unavailable.');
 }
 public function render(GatewayPdfDocument $document):string
 {
  $options=$this->options();$dompdf=new Dompdf($options);$dompdf->setPaper($document->paper,$document->orientation);$dompdf->loadHtml($document->html,'UTF-8');$dompdf->render();$bytes=$dompdf->output();if(!is_string($bytes)||!str_starts_with($bytes,'%PDF-'))throw new \RuntimeException('PDF rendering failed safely.');return$bytes;
 }
 public function options():Options
 {
  $options=new Options();$options->setChroot($this->chroot);$options->setIsRemoteEnabled(false);$options->setIsJavascriptEnabled(false);$options->setDefaultFont('DejaVu Sans');$options->setIsHtml5ParserEnabled(true);return$options;
 }
}
