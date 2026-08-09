<?php declare(strict_types=1);namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\ClockInterface;use NpmGateway\Contracts\GatewayPdfRendererInterface;use NpmGateway\Http\AuthenticatedRequestContext;use NpmGateway\Http\Request;use NpmGateway\Http\Response;use NpmGateway\Services\CorporateAccessService;use NpmGateway\Services\OperationsRmAuditOverviewService;use NpmGateway\ValueObjects\GatewayPdfDocument;use NpmGateway\ValueObjects\RmAuditOverviewCriteria;
final readonly class OperationsRmAuditPdfController
{
 public function __construct(private CorporateAccessService $access,private OperationsRmAuditOverviewService $audits,private GatewayPdfRendererInterface $pdf,private ClockInterface $clock,private string $views){}
 public function download(Request $request,AuthenticatedRequestContext $context):Response
 {
  if(!$this->access->canAccessCategory($context,'operations'))return new Response(403,'Forbidden',['Cache-Control'=>'private, no-store']);$properties=$this->audits->properties();$criteria=RmAuditOverviewCriteria::fromQuery($request->query,$this->clock->now(),$properties);if($criteria->errors!==[])return new Response(422,'The RM Audit report criteria are invalid.',['Cache-Control'=>'private, no-store']);$report=$this->audits->report($criteria);$propertyName='All Properties';foreach($properties as$property)if($criteria->propertyPublicId===$property['public_id']){$propertyName=(string)$property['display_name'];break;}$generatedAt=$this->clock->now();$html=$this->html(compact('criteria','report','propertyName','generatedAt'));
  try{$bytes=$this->pdf->render(new GatewayPdfDocument('RM Audits',$html));}catch(\Throwable$exception){error_log(json_encode(['report'=>'operations_rm_audits','failure_code'=>'render_failed','exception_class'=>$exception::class],JSON_UNESCAPED_SLASHES));return new Response(500,'The RM Audit PDF could not be generated safely.',['Cache-Control'=>'private, no-store']);}
  return new Response(200,$bytes,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$this->filename($criteria,$propertyName).'"','Content-Length'=>(string)strlen($bytes),'Cache-Control'=>'private, no-store','Pragma'=>'no-cache','X-Content-Type-Options'=>'nosniff']);
 }
 private function html(array $vars):string{extract($vars);ob_start();require$this->views.'/pdf/operations-rm-audits.php';return(string)ob_get_clean();}
 private function filename(RmAuditOverviewCriteria $criteria,string $propertyName):string{$property='';if($criteria->propertyPublicId!==''){$slug=trim((string)preg_replace('/[^A-Za-z0-9]+/','-',$propertyName),'-');$property='_'.($slug!==''?$slug:'Property');}return'RM-Audits'.$property.'_'.$criteria->from.'_to_'.$criteria->to.'.pdf';}
}
