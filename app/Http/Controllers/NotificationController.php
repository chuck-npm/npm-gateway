<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\NotificationAcknowledgmentService;
use NpmGateway\Services\NotificationQueryService;
use NpmGateway\Services\NotificationPresentationService;
use NpmGateway\Support\FlashSession;
final class NotificationController
{
 public function __construct(private readonly NotificationQueryService $queries,private readonly NotificationAcknowledgmentService $ack,private readonly NotificationPresentationService $presentation,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
 public function index(Request $request,AuthenticatedRequestContext $context):Response{$user=$context->user;$requested=(string)($request->query['view']??'');$filter=in_array($requested,['acknowledged','informational'],true)?$requested:'outstanding';$notices=$this->queries->listing($user->id,$filter);$notificationCount=$this->queries->count($user->id);$csrfToken=$this->csrf->token();$logoutCsrfToken=$csrfToken;$navbarCorporateItems=$this->tools->tools($context);$canCreateNotice=false;foreach($navbarCorporateItems as $item){if($item->key==='company-notices'&&$item->enabled){$canCreateNotice=true;break;}}$success=(string)$this->flash->pull('notification_success','');ob_start();require $this->views.'/notifications/index.php';return new Response(200,(string)ob_get_clean());}
 public function show(string $publicId,AuthenticatedRequestContext $context):Response{$notice=$this->ack->view($publicId,$context->user);if($notice===null)return new Response(404,'Not Found');$displayPayload=$notice['notification_type']==='employee_created'?$this->presentation->employeeFields($notice['payload']):$notice['payload'];$user=$context->user;$notificationCount=$this->queries->count($user->id);$csrfToken=$this->csrf->token();$logoutCsrfToken=$csrfToken;$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/notifications/show.php';return new Response(200,(string)ob_get_clean());}
 public function acknowledge(Request $request,string $publicId,AuthenticatedRequestContext $context):Response{if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');if($this->ack->acknowledge($publicId,$context->user)===null)return new Response(404,'Not Found');$this->flash->put('notification_success','Notice acknowledged successfully.');return Response::redirect('/notifications/'.$publicId);}
}
