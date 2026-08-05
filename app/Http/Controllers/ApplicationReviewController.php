<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Exceptions\Domain\CommunityActionPropertyForbiddenException;
use NpmGateway\Exceptions\Domain\CommunityActionPropertyNotFoundException;
use NpmGateway\Exceptions\Domain\InvalidApplicationReviewException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\ApplicationReviewService;
use NpmGateway\Services\ApplicationReviewQueryService;
use NpmGateway\Services\CommunityActionContextResolver;
use NpmGateway\Support\FlashSession;
final class ApplicationReviewController
{
 public function __construct(private readonly CommunityActionContextResolver $resolver,private readonly ApplicationReviewQueryService $repo,private readonly ApplicationReviewService $service,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
 public function index(Request $r,string $slug,AuthenticatedRequestContext $request):Response{if(($resolved=$this->context($request,$slug)) instanceof Response)return $resolved;$context=$resolved;$status=(string)($r->query['status']??'');$search=trim((string)($r->query['search']??''));$reviews=$this->repo->managerList($context->propertyId,$status,$search);$success=(string)$this->flash->pull('application_review_success','');return $this->render('index',$request,compact('context','reviews','status','search','success'));}
 public function create(string $slug,AuthenticatedRequestContext $request):Response{if(($resolved=$this->context($request,$slug)) instanceof Response)return $resolved;$context=$resolved;$input=(array)$this->flash->pull('application_review_input',[]);$errors=(array)$this->flash->pull('application_review_errors',[]);return $this->render('create',$request,compact('context','input','errors'));}
 public function store(Request $r,string $slug,AuthenticatedRequestContext $request):Response{if(($resolved=$this->context($request,$slug)) instanceof Response)return $resolved;$context=$resolved;if(!$this->csrf->valid($r->post['_token']??null))return new Response(419,'Invalid request.',['Cache-Control'=>'private, no-store']);try{$result=$this->service->submit($context,$r->post);$this->flash->put('application_review_success',$result['email_sent']?'Application review submitted successfully.':'Application review submitted, but the review email could not be delivered.');return Response::redirect('/community-actions/'.$context->propertySlug.'/application-reviews/'.$result['public_id']);}catch(InvalidApplicationReviewException $e){$this->flash->put('application_review_input',$e->input);$this->flash->put('application_review_errors',$e->errors);return Response::redirect('/community-actions/'.$context->propertySlug.'/application-reviews/create');}catch(CommunityActionPropertyForbiddenException){return new Response(403,'Forbidden',['Cache-Control'=>'private, no-store']);}catch(\Throwable){return new Response(500,'The application review could not be submitted safely.',['Cache-Control'=>'private, no-store']);}}
 public function show(string $slug,string $publicId,AuthenticatedRequestContext $request):Response{if(($resolved=$this->context($request,$slug)) instanceof Response)return $resolved;$context=$resolved;$review=$this->repo->managerDetail($publicId,$context->propertyId);if($review===null)return new Response(404,'Not Found',['Cache-Control'=>'private, no-store']);$success=(string)$this->flash->pull('application_review_success','');return $this->render('show',$request,compact('context','review','success'));}
 private function context(AuthenticatedRequestContext $request,string $slug):mixed{try{return $this->resolver->resolve($request,$slug);}catch(CommunityActionPropertyNotFoundException){return new Response(404,'Not Found',['Cache-Control'=>'private, no-store']);}catch(CommunityActionPropertyForbiddenException){return new Response(403,'Forbidden',['Cache-Control'=>'private, no-store']);}}
 private function render(string $view,AuthenticatedRequestContext $request,array $vars):Response{$user=$request->user;$csrfToken=$this->csrf->token();$logoutCsrfToken=$csrfToken;$navbarCorporateItems=$this->tools->tools($request);extract($vars,EXTR_SKIP);ob_start();require $this->views.'/community-actions/application-reviews/'.$view.'.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
}
