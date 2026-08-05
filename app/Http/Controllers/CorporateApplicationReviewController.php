<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Exceptions\Domain\ApplicationReviewAlreadyCompletedException;
use NpmGateway\Exceptions\Domain\InvalidApplicationReviewException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\ApplicationReviewService;
use NpmGateway\Services\ApplicationReviewQueryService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Support\FlashSession;
final class CorporateApplicationReviewController
{
 public function __construct(private readonly CorporateAccessService $access,private readonly ApplicationReviewQueryService $repo,private readonly ApplicationReviewService $service,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
 public function index(Request $r,AuthenticatedRequestContext $c):Response{if(!$this->allowed($c))return $this->denied();$status=(string)($r->query['status']??'');$propertyPublicId=(string)($r->query['property']??'');$search=trim((string)($r->query['search']??''));$reviews=$this->repo->corporateQueue($status,$propertyPublicId,$search);$counts=$this->repo->counts();$properties=$this->repo->properties();return $this->render('index',$c,compact('reviews','counts','properties','status','propertyPublicId','search'));}
 public function show(string $publicId,AuthenticatedRequestContext $c):Response{if(!$this->allowed($c))return $this->denied();$review=$this->repo->corporateDetail($publicId);if($review===null)return new Response(404,'Not Found',['Cache-Control'=>'private, no-store']);$input=(array)$this->flash->pull('application_review_decision_input',[]);$errors=(array)$this->flash->pull('application_review_decision_errors',[]);$success=(string)$this->flash->pull('application_review_decision_success','');$decisionError=(string)$this->flash->pull('application_review_decision_error','');return $this->render('show',$c,compact('review','input','errors','success','decisionError'));}
 public function decide(Request $r,string $publicId,AuthenticatedRequestContext $c):Response{if(!$this->allowed($c))return $this->denied();if(!$this->csrf->valid($r->post['_token']??null))return new Response(419,'Invalid request.',['Cache-Control'=>'private, no-store']);$decisionPost=['decision'=>$r->post['decision']??null,'reviewer_comments'=>$r->post['reviewer_comments']??null];try{$result=$this->service->decide($publicId,$decisionPost,$c->user);$decision=$decisionPost['decision']==='approved'?'approved':'denied';$this->flash->put('application_review_decision_success',$result['email_sent']?'Application review '.$decision.' successfully.':'Application review '.$decision.', but the decision email could not be delivered.');return Response::redirect('/corporate/application-reviews/'.$publicId);}catch(InvalidApplicationReviewException $e){$this->flash->put('application_review_decision_input',$e->input);$this->flash->put('application_review_decision_errors',$e->errors);return Response::redirect('/corporate/application-reviews/'.$publicId);}catch(ApplicationReviewAlreadyCompletedException){$this->flash->put('application_review_decision_error','This application review has already been completed.');return Response::redirect('/corporate/application-reviews/'.$publicId);}catch(\OutOfBoundsException){return new Response(404,'Not Found',['Cache-Control'=>'private, no-store']);}catch(\Throwable){return new Response(500,'The application review decision could not be recorded safely.',['Cache-Control'=>'private, no-store']);}}
 private function allowed(AuthenticatedRequestContext $c):bool{return $this->access->canAccessCategory($c,'application-reviews');}private function denied():Response{return new Response(403,'Forbidden',['Cache-Control'=>'private, no-store']);}
 private function render(string $view,AuthenticatedRequestContext $c,array $vars):Response{$user=$c->user;$csrfToken=$this->csrf->token();$logoutCsrfToken=$csrfToken;$navbarCorporateItems=$this->tools->tools($c);extract($vars,EXTR_SKIP);ob_start();require $this->views.'/corporate/application-reviews/'.$view.'.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
}
