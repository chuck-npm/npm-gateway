<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\ValueObjects\GatewayEmailMessage;
use PHPMailer\PHPMailer\PHPMailer;
final class ApplicationReviewEmailSender
{
 private readonly GatewayEmailRenderer $renderer;
 public function __construct(private readonly array $config,private readonly ?\Closure $delivery=null,?GatewayEmailRenderer $renderer=null){$this->renderer=$renderer??new GatewayEmailRenderer();}
 public function sendSubmission(array $review):bool{try{return $this->send('Application Review Submitted — '.$review['property_name'],$this->submissionContent($review));}catch(\Throwable){return false;}}
 public function sendDecision(array $review):bool{try{return $this->send('Application Review '.($review['status']==='approved'?'Approved':'Denied').' — '.$review['prospect_name'],$this->decisionContent($review));}catch(\Throwable){return false;}}
 public function submissionContent(array $review):array
 {
  $url=$this->url('/corporate/application-reviews/'.$review['public_id']);$sections=[];if(trim((string)$review['manager_comments'])!=='')$sections[]=['title'=>'Comments for Reviewer','body'=>(string)$review['manager_comments']];
  return $this->renderer->render(new GatewayEmailMessage('A new application review is awaiting Corporate review.','APPLICATION REVIEWS','Application Review Submitted',(string)$review['property_name'],'Pending Review','pending',[
   ['label'=>'Prospect','value'=>(string)$review['prospect_name']],['label'=>'Property','value'=>(string)$review['property_name'],'emphasized'=>true],['label'=>'Submitted By','value'=>(string)$review['submitted_by_name']],['label'=>'Submitted At','value'=>$this->date((string)$review['submitted_at'])],['label'=>'Rent Manager Documents','value'=>'Confirmed'],
  ],$sections,'Review Application',$url));
 }
 public function decisionContent(array $review):array
 {
  $approved=$review['status']==='approved';$decision=$approved?'Approved':'Denied';$url=$this->url('/community-actions/'.$review['property_slug'].'/application-reviews/'.$review['public_id']);
  return $this->renderer->render(new GatewayEmailMessage('An application review decision has been recorded.','APPLICATION REVIEWS','Application Review '.$decision,(string)$review['property_name'],$decision,$approved?'success':'danger',[
   ['label'=>'Prospect','value'=>(string)$review['prospect_name']],['label'=>'Property','value'=>(string)$review['property_name'],'emphasized'=>true],['label'=>'Decision','value'=>$decision],['label'=>'Reviewed By','value'=>(string)$review['reviewed_by_name']],['label'=>'Reviewed At','value'=>$this->date((string)$review['reviewed_at'])],
  ],[['title'=>'Reviewer Comments','body'=>(string)$review['reviewer_comments']]],'View Application Review',$url));
 }
 private function date(string $value):string{try{return (new \DateTimeImmutable($value))->format('F j, Y \a\t g:i A');}catch(\Throwable){return $value;}}
 private function url(string $path):string{return rtrim((string)($this->config['app_url']??''),'/').$path;}
 private function send(string $subject,array $content):bool
 {
  $test=($this->config['test_mode']??false)===true;$recipient=trim((string)($this->config['test_email']??''));if(!$test||filter_var($recipient,FILTER_VALIDATE_EMAIL)===false)return false;
  if($this->delivery){try{return (bool)($this->delivery)($recipient,$subject,$content['html'],$content['text']);}catch(\Throwable){return false;}}
  try{$smtp=HrEmployeeNotificationConfig::fromArray((array)($this->config['smtp']??[]));$mail=new PHPMailer(true);$mail->isSMTP();$mail->Host=$smtp->host;$mail->Port=$smtp->port;$mail->SMTPAuth=true;$mail->Username=$smtp->username;$mail->Password=$smtp->password;$mail->SMTPSecure=$smtp->secure==='ssl'?PHPMailer::ENCRYPTION_SMTPS:PHPMailer::ENCRYPTION_STARTTLS;$mail->CharSet=PHPMailer::CHARSET_UTF8;$mail->setFrom($smtp->fromAddress,$smtp->fromName);$mail->addAddress($recipient);$mail->Subject=mb_substr($subject,0,200);$mail->isHTML(true);$mail->Body=$content['html'];$mail->AltBody=$content['text'];$mail->send();return true;}catch(\Throwable){return false;}
 }
}
