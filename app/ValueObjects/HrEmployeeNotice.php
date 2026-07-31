<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class HrEmployeeNotice
{
    public function __construct(public array $recipients,public string $senderAddress,public string $senderName,public string $subject,public string $htmlBody,public string $textBody,#[\SensitiveParameter] private string $initialPassword){}
    public function initialPassword():string{return $this->initialPassword;}
}
