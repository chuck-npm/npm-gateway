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
        if(!$this->access->canAccessCategory($context,'human-resources'))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->corporateTools->tools($context);$cards=[new ToolCard('employees','Employees','Manage employee records, operational assignments, and Gateway accounts.','Human Resources','Manage employees','/human-resources/employees',true,10,null,null,'hr.employees.index'),new ToolCard('properties','Properties','Manage company property contact and operational information.','Human Resources','Manage properties','/human-resources/properties',true,20,null,null,'hr.properties.index'),new ToolCard('emergency-contacts','Emergency Contact Information','Review completion and follow up on employee emergency contact information.','ECI','Open ECI','/corporate/human-resources/emergency-contacts',true,30,null,null,'hr.emergency-contacts.index'),new ToolCard('credit-cards','Credit Cards','Manage employee credit-card assignments and records.','Human Resources','Module planned',null,false,40,'Planned')];ob_start();require $this->views.'/human-resources/index.php';return new Response(200,(string)ob_get_clean());
    }
}
