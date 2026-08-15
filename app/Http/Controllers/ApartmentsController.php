<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Exceptions\Domain\InvalidApartmentsWorkbookException;
use NpmGateway\Http\{AuthenticatedRequestContext,Request,Response};
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\{ApartmentsService,CallLogAccessPolicy};
use NpmGateway\Support\FlashSession;
final readonly class ApartmentsController
{
 public function __construct(private CallLogAccessPolicy$access,private ApartmentsService$service,private CorporateToolsProviderInterface$tools,private CsrfService$csrf,private FlashSession$flash,private string$views){}
 public function index(AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$history=$this->service->history();$success=(string)$this->flash->pull('apartments_success','');$error=(string)$this->flash->pull('apartments_error','');ob_start();require$this->views.'/admin/apartments/index.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 public function upload(AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$csrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$error=(string)$this->flash->pull('apartments_error','');ob_start();require$this->views.'/admin/apartments/upload.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 public function store(Request$request,AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');try{$result=$this->service->import((array)($request->files['apartments_workbook']??[]),$context->user);$this->flash->put('apartments_success','Apartments.com data imported successfully: '.number_format($result['calls']).' calls and '.number_format($result['email_leads']).' email leads.');$redirect='/admin/apartments';}catch(InvalidApartmentsWorkbookException$e){$this->flash->put('apartments_error',$e->getMessage());$redirect='/admin/apartments/upload';}catch(\Throwable){$this->flash->put('apartments_error','The Apartments.com workbook could not be imported safely. No records were imported.');$redirect='/admin/apartments/upload';}if(strtolower((string)($request->server['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest')return Response::json(['ok'=>$redirect==='/admin/apartments','redirect'=>$redirect,'message'=>(string)$this->flash->pull('apartments_error','')],$redirect==='/admin/apartments'?200:422);return Response::redirect($redirect);}
}
