<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CategoryAccessAdministrationService;
use NpmGateway\Services\CategoryAccessPayloadParser;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Support\FlashSession;
use NpmGateway\ValueObjects\ToolCard;
final class AdminController
{
    public function __construct(private readonly CorporateAccessService $access,private readonly CategoryAccessAdministrationService $administration,private readonly CategoryAccessPayloadParser $payloads,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
    public function index(AuthenticatedRequestContext $context):Response{if(!$this->allowed($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$cards=[new ToolCard('category-access','Category Access','Manage access to Corporate Gateway categories.','Administration','Manage access','/admin/category-access',true,10,null,null,'admin.category-access.index')];ob_start();require $this->views.'/admin/index.php';return new Response(200,(string)ob_get_clean());}
    public function categoryAccess(AuthenticatedRequestContext $context):Response{if(!$this->allowed($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$csrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$matrix=$this->administration->matrix();$error=(string)$this->flash->pull('admin_access_error','');$success=(string)$this->flash->pull('admin_access_success','');ob_start();require $this->views.'/admin/category-access.php';return new Response(200,(string)ob_get_clean());}
    public function save(Request $request,AuthenticatedRequestContext $context):Response{if(!$this->allowed($context))return new Response(403,'Forbidden');if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');try{$changes=$this->administration->applyChanges($this->payloads->parse($request->post),$context->user);$this->flash->put('admin_access_success',$changes===0?'No category access changes were needed.':'Category access changes saved.');}catch(\InvalidArgumentException $e){$this->flash->put('admin_access_error',$e->getMessage());}return Response::redirect('/admin/category-access');}
    private function allowed(AuthenticatedRequestContext $context):bool{return $this->access->canAccessCategory($context,'admin');}
}
