<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class CompanyAnnouncementEmail
{
 /** @param list<array{label:string,value:string,type?:string}> $fields */
 public function __construct(public string $brandLabel,public string $category,public string $title,public string $introduction,public array $fields,public string $closing,public string $footer){}
}
