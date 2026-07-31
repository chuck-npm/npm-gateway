<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\ValueObjects\ToolCard;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
final class HumanResourcesController
{
    public function __construct(private readonly CorporateAccessService $access,private readonly CorporateToolsProviderInterface $corporateTools,private readonly CsrfService $csrf,private readonly string $views){}
    public function index(AuthenticatedRequestContext $context):Response
    {
        if(!$this->access->allows($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$showCorporateTools=true;$navbarCorporateItems=$this->corporateTools->tools();$cards=[new ToolCard('employees','Employees','View company employee contact information.','Human Resources','Open employees','/employees',true,10,null,null,'employees.index'),new ToolCard('properties','Properties','Manage company property contact and operational information.','Human Resources','Manage properties','/human-resources/properties',true,20,null,null,'hr.properties.index'),new ToolCard('credit-cards','Credit Cards','Manage employee credit-card assignments and records.','Human Resources','Module planned',null,false,30,'Planned')];ob_start();require $this->views.'/human-resources/index.php';return new Response(200,(string)ob_get_clean());
    }
}
