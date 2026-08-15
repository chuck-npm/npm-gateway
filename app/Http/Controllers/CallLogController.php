<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Exceptions\Domain\InvalidCallLogWorkbookException;
use NpmGateway\Http\{AuthenticatedRequestContext,Request,Response};
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\{CallLogAccessPolicy,CallLogService};
use NpmGateway\Support\{FlashSession,GatewayDateTimeFormatter,PhoneFormatter};
final readonly class CallLogController
{
 public function __construct(private CallLogAccessPolicy$access,private CallLogService$service,private CorporateToolsProviderInterface$tools,private CsrfService$csrf,private FlashSession$flash,private PhoneFormatter$phones,private string$views){}
 public function index(Request$request,AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$page=$this->service->page($request->query);$success=(string)$this->flash->pull('call_log_success','');$error=(string)$this->flash->pull('call_log_error','');$phones=$this->phones;ob_start();require$this->views.'/admin/call-logs/index.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 public function upload(AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$csrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$error=(string)$this->flash->pull('call_log_error','');ob_start();require$this->views.'/admin/call-logs/upload.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 public function store(Request$request,AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');try{$count=$this->service->import((array)($request->files['call_log']??[]),$context->user);$this->flash->put('call_log_success',number_format($count).' call records imported successfully.');$redirect='/admin/call-logs';}catch(InvalidCallLogWorkbookException$e){$this->flash->put('call_log_error',$e->getMessage());$redirect='/admin/call-logs/upload';}catch(\Throwable){$this->flash->put('call_log_error','The Call Log could not be imported safely. No records were imported.');$redirect='/admin/call-logs/upload';}if(strtolower((string)($request->server['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest')return Response::json(['ok'=>$redirect==='/admin/call-logs','redirect'=>$redirect,'message'=>(string)$this->flash->pull('call_log_error','')],$redirect==='/admin/call-logs'?200:422);return Response::redirect($redirect);}
}
