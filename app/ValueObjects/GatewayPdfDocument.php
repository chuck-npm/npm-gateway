<?php declare(strict_types=1);namespace NpmGateway\ValueObjects;
final readonly class GatewayPdfDocument
{
 public function __construct(public string $title,public string $html,public string $paper='letter',public string $orientation='portrait')
 {
  if(trim($title)===''||trim($html)==='')throw new \InvalidArgumentException('PDF title and trusted HTML are required.');
  if(!in_array($paper,['letter','legal','a4'],true)||!in_array($orientation,['portrait','landscape'],true))throw new \InvalidArgumentException('Unsupported PDF page configuration.');
 }
}
