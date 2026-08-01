<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\ValueObjects\ToolCard;
interface CorporateToolsProviderInterface
{
    /** @return list<ToolCard> */
    public function tools(AuthenticatedRequestContext $context):array;
}
