<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Services\EmployeeDirectoryCriteriaFactory;
use NpmGateway\Services\EmployeeDirectoryService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Security\CsrfService;
final class EmployeeWorkspaceController
{
    public function __construct(private readonly EmployeeDirectoryCriteriaFactory $criteriaFactory,private readonly EmployeeDirectoryService $directory,private readonly CorporateAccessService $corporateAccess,private readonly CorporateToolsProviderInterface $corporateTools,private readonly CsrfService $csrf,private readonly string $views) {}
    public function index(Request $request,AuthenticatedRequestContext $context):Response
    {
        $user=$context->user;$logoutCsrfToken=$this->csrf->token();$directoryPage=$this->directory->search($this->criteriaFactory->fromQuery($request->query));$showCorporateTools=$this->corporateAccess->allows($context);$navbarCorporateItems=$showCorporateTools?$this->corporateTools->tools():[];ob_start();require $this->views.'/employees/index.php';return new Response(200,(string)ob_get_clean());
    }
}
