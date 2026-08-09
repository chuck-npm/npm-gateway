<?php declare(strict_types=1);namespace NpmGateway\Contracts;use NpmGateway\ValueObjects\GatewayPdfDocument;
interface GatewayPdfRendererInterface{public function render(GatewayPdfDocument $document):string;}
