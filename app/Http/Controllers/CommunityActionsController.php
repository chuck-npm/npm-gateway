<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\PropertyAccessService;
use NpmGateway\Services\CommunityActionContextResolver;
use NpmGateway\Services\CommunityActionProvider;
use NpmGateway\Exceptions\Domain\CommunityActionPropertyForbiddenException;
use NpmGateway\Exceptions\Domain\CommunityActionPropertyNotFoundException;
final class CommunityActionsController
{
 public function __construct(private readonly PropertyAccessService $access,private readonly CommunityActionContextResolver $resolver,private readonly CommunityActionProvider $actions,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly string $views){}
 public function index(AuthenticatedRequestContext $context):Response{$properties=$this->access->accessibleActiveProperties($context);return $this->render('index',$context,compact('properties'));}
 public function show(string $slug,AuthenticatedRequestContext $requestContext):Response{try{$context=$this->resolver->resolve($requestContext,$slug);}catch(CommunityActionPropertyNotFoundException){return $this->denied(404,'Not Found');}catch(CommunityActionPropertyForbiddenException){return $this->denied(403,'Forbidden');}$actions=$this->actions->actions();return $this->render('show',$requestContext,compact('context','actions'));}
 public function action(string $slug,string $segment,AuthenticatedRequestContext $requestContext):Response{try{$context=$this->resolver->resolve($requestContext,$slug);}catch(CommunityActionPropertyNotFoundException){return $this->denied(404,'Not Found');}catch(CommunityActionPropertyForbiddenException){return $this->denied(403,'Forbidden');}$action=$this->actions->findByRouteSegment($segment);if($action===null)return $this->denied(404,'Not Found');return $this->render('action',$requestContext,compact('context','action'));}
 private function denied(int $status,string $body):Response{return new Response($status,$body,['Cache-Control'=>'private, no-store']);}
 private function render(string $view,AuthenticatedRequestContext $requestContext,array $vars):Response{extract($vars,EXTR_SKIP);$user=$requestContext->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($requestContext);ob_start();require $this->views.'/community-actions/'.$view.'.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
}
