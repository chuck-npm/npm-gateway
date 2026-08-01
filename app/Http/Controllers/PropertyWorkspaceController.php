<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Exceptions\Domain\InvalidPropertyDataException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\PropertyAdministrationService;
use NpmGateway\Services\PropertyDirectoryCriteriaFactory;
use NpmGateway\Services\PropertyQueryService;
use NpmGateway\Support\FlashSession;
final class PropertyWorkspaceController
{
    public function __construct(private readonly PropertyDirectoryCriteriaFactory $criteria,private readonly PropertyQueryService $directory,private readonly PropertyAdministrationService $administration,private readonly CorporateAccessService $access,private readonly CorporateToolsProviderInterface $corporateTools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
    public function directory(Request $request,AuthenticatedRequestContext $context):Response{return $this->renderDirectory($request,$context,false);}
    public function hrDirectory(Request $request,AuthenticatedRequestContext $context):Response{return $this->allowed($context)?$this->renderDirectory($request,$context,true):new Response(403,'Forbidden');}
    public function create(AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$csrfToken=$this->csrf->token();$errors=(array)$this->flash->pull('property_errors',[]);$input=(array)$this->flash->pull('property_input',[]);$navbarCorporateItems=$this->corporateTools->tools($context);ob_start();require $this->views.'/human-resources/properties/create.php';return new Response(200,(string)ob_get_clean());
    }
    public function store(Request $request,AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');try{$this->administration->create($request->post,$context->user);$this->flash->put('success','Property added successfully.');}catch(InvalidPropertyDataException $e){$this->flash->put('property_errors',$e->errors);$this->flash->put('property_input',$e->input);return Response::redirect('/human-resources/properties/create');}return Response::redirect('/human-resources/properties');
    }
    private function renderDirectory(Request $request,AuthenticatedRequestContext $context,bool $hr):Response
    {
        $user=$context->user;$logoutCsrfToken=$this->csrf->token();$directoryPage=$this->directory->search($this->criteria->fromQuery($request->query));$success=$hr?(string)$this->flash->pull('success',''):'';$navbarCorporateItems=$this->corporateTools->tools($context);ob_start();require $this->views.'/properties/index.php';return new Response(200,(string)ob_get_clean());
    }
    private function allowed(AuthenticatedRequestContext $context):bool{return $this->access->canAccessCategory($context,'human-resources');}
}
