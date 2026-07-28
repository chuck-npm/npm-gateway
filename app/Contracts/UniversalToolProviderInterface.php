<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\ToolCard;
interface UniversalToolProviderInterface
{
    /** @return list<ToolCard> */
    public function tools(): array;
}
