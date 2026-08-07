<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\Services\RmCorrectionStatus;
use NpmGateway\ValueObjects\GatewayEmailMessage;
use PHPMailer\PHPMailer\PHPMailer;
final class RmCorrectionEmailSender
{
 private readonly GatewayEmailRenderer $renderer;
 public function __construct(private readonly array $config,private readonly ?\Closure $delivery=null,?GatewayEmailRenderer $renderer=null){$this->renderer=$renderer??new GatewayEmailRenderer();}
 public function submission(array $r):bool{try{return $this->deliver('submission',$this->reviewer(),"RM Correction Submitted — {$r['property_name']}",$this->content($r,'RM Correction Submitted','Pending Review','pending','Correction Request',$r['correction_request'],'Review Request','/corporate/rm-corrections/'.$r['public_id']));}catch(\Throwable $e){return $this->failure('submission','rendering_failure',$e);}}
 public function response(array $r,string $additional):bool{try{return $this->deliver('manager_response',$this->reviewer(),"RM Correction Updated — {$r['property_name']}",$this->content($r,'Additional Information Submitted','Pending Review','pending','Additional Information',$additional,'Review Request','/corporate/rm-corrections/'.$r['public_id']));}catch(\Throwable $e){return $this->failure('manager_response','rendering_failure',$e);}}
 public function decision(array $r,string $comments):bool{try{$label=RmCorrectionStatus::LABELS[$r['status']];$tone=match($r['status']){'approved'=>'success','denied'=>'danger',default=>'informational'};$action=$r['status']==='more_information_needed'?'Respond to Request':'View RM Correction';$path='/community-actions/'.$r['property_slug'].'/rm-corrections/'.$r['public_id'].($r['status']==='more_information_needed'?'/respond':'');return $this->deliver('decision_'.(string)$r['status'],(string)$r['manager_email'],"RM Correction {$label} — {$r['property_name']}",$this->content($r,$label,$label,$tone,'Comments',$comments,$action,$path));}catch(\Throwable $e){return $this->failure('decision','rendering_failure',$e);}}
 private function content(array $r,string $title,string $status,string $tone,string $section,string $body,string $action,string $path):array{$rows=[['label'=>'Property','value'=>(string)$r['property_name'],'emphasized'=>true],['label'=>'Lot / Address','value'=>(string)$r['lot_address']],['label'=>'Tenant','value'=>(string)$r['tenant_name']],['label'=>'Submitted By','value'=>(string)$r['submitted_by_name']],['label'=>'Submitted At','value'=>$this->date((string)$r['submitted_at'])]];if(trim((string)($r['reviewed_by_name']??''))!==''&&($r['reviewed_at']??null)!==null){$rows[]=['label'=>'Reviewed By','value'=>(string)$r['reviewed_by_name']];$rows[]=['label'=>'Reviewed At','value'=>$this->date((string)$r['reviewed_at'])];}return $this->renderer->render(new GatewayEmailMessage('RM Correction workflow update.','RM CORRECTIONS',$title,(string)$r['property_name'],$status,$tone,$rows,[['title'=>$section,'body'=>$body]],$action,$this->url($path)));}
 private function reviewer():string{return trim((string)($this->config['reviewer_email']??''));}
 private function deliver(string $messageType,string $recipient,string $subject,array $content):bool
 {
  $test=($this->config['test_mode']??false)===true;$mode=$test?'test':'production';if($test)$recipient=trim((string)($this->config['test_email']??''));if(filter_var($recipient,FILTER_VALIDATE_EMAIL)===false)return $this->failure($messageType,'recipient_unavailable',null,$mode);
  if($this->delivery){try{return (bool)($this->delivery)($recipient,$subject,$content['html'],$content['text']);}catch(\Throwable $e){return $this->failure($messageType,'transport_failure',$e,$mode);}}
  try{$smtp=HrEmployeeNotificationConfig::fromArray((array)($this->config['smtp']??[]));$m=new PHPMailer(true);$m->isSMTP();$m->Host=$smtp->host;$m->Port=$smtp->port;$m->SMTPAuth=true;$m->Username=$smtp->username;$m->Password=$smtp->password;$m->SMTPSecure=$smtp->secure==='ssl'?PHPMailer::ENCRYPTION_SMTPS:PHPMailer::ENCRYPTION_STARTTLS;$m->CharSet=PHPMailer::CHARSET_UTF8;$m->setFrom($smtp->fromAddress,$smtp->fromName);$m->addAddress($recipient);$m->Subject=mb_substr($subject,0,200);$m->isHTML(true);$m->Body=$content['html'];$m->AltBody=$content['text'];$m->send();return true;}catch(\Throwable $e){return $this->failure($messageType,'transport_failure',$e,$mode);}
 }
 private function failure(string $messageType,string $code,?\Throwable $error=null,?string $mode=null):false{error_log((string)json_encode(['workflow'=>'rm_corrections','message_type'=>$messageType,'failure_code'=>$code,'exception_class'=>$error===null?null:$error::class,'recipient_mode'=>$mode??((($this->config['test_mode']??false)===true)?'test':'production')],JSON_UNESCAPED_SLASHES));return false;}
 private function url(string $p):string{return rtrim((string)($this->config['app_url']??''),'/').$p;}
 private function date(string $v):string{try{return (new \DateTimeImmutable($v))->format('F j, Y \a\t g:i A');}catch(\Throwable){return $v;}}
}
