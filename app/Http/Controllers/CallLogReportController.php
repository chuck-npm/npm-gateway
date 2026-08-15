<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\{AuthenticatedRequestContext,Request,Response};
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\{CallLogAccessPolicy,CallLogReportService};
use NpmGateway\ValueObjects\ToolCard;
final readonly class CallLogReportController
{
 public function __construct(private CallLogAccessPolicy$access,private CallLogReportService$reports,private CorporateToolsProviderInterface$tools,private CsrfService$csrf,private string$views){}
 public function index(AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$cards=[new ToolCard('facebook-performance','Facebook Performance Report','Call volume and answer performance by property.','Call Log Reports','Open Report','/admin/call-log-reports/facebook-performance',true,10,null,null,'admin.call-log-reports.facebook-performance')];ob_start();require$this->views.'/admin/call-log-reports/index.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 public function facebookPerformance(Request$request,AuthenticatedRequestContext$context):Response{if(!$this->access->allows($context->user))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);$report=$this->reports->facebookPerformance($request->query);ob_start();require$this->views.'/admin/call-log-reports/facebook-performance.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
}
