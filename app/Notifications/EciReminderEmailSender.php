<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use PHPMailer\PHPMailer\PHPMailer;
final class EciReminderEmailSender
{
 public function __construct(private readonly ?HrEmployeeNotificationConfig $config,private readonly string $appUrl,private readonly ?\Closure $factory=null){}
 public function send(string $destination,array $employee):bool
 {
  try{
   if($this->config===null)return false;$mail=$this->factory?($this->factory)():new PHPMailer(true);$mail->isSMTP();$mail->Host=$this->config->host;$mail->Port=$this->config->port;$mail->SMTPAuth=true;$mail->Username=$this->config->username;$mail->Password=$this->config->password;$mail->SMTPSecure=$this->config->secure==='ssl'?PHPMailer::ENCRYPTION_SMTPS:PHPMailer::ENCRYPTION_STARTTLS;$mail->CharSet=PHPMailer::CHARSET_UTF8;$mail->setFrom($this->config->fromAddress,$this->config->fromName);$mail->addAddress($destination);$base=rtrim($this->appUrl,'/');
   if($employee['employee_class']==='maintenance'){$mail->Subject='Emergency Contact Information Required for Maintenance Employee';$link=$base.'/manager/maintenance/'.$employee['public_id'].'/emergency-contact';$name=htmlspecialchars((string)$employee['display_name'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$safeLink=htmlspecialchars($link,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$mail->Body='<p>Manager,</p><p>Emergency Contact Information is currently missing for maintenance employee:</p><p><strong>'.$name.'</strong></p><p>Please obtain this information from the employee and enter it into NPM Gateway.</p><p>Keeping Emergency Contact Information current allows NPM to contact someone on an employee’s behalf during an emergency.</p><p><a href="'.$safeLink.'">Open Employee ECI</a></p>';$mail->AltBody="Manager,\n\nEmergency Contact Information is currently missing for maintenance employee:\n\n".$employee['display_name']."\n\nPlease obtain this information from the employee and enter it into NPM Gateway.\n\nKeeping Emergency Contact Information current allows NPM to contact someone on an employee’s behalf during an emergency.\n\n{$link}";}
   else{$mail->Subject='Please Complete Your Emergency Contact Information';$link=$base.'/my/emergency-contact';$first=htmlspecialchars((string)$employee['first_name'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$safeLink=htmlspecialchars($link,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$mail->Body='<p>'.$first.',</p><p>Please sign in to NPM Gateway and complete your Emergency Contact Information.</p><p>Keeping this information current allows NPM to contact someone on your behalf during an emergency.</p><p><a href="'.$safeLink.'">Complete Emergency Contact Information</a></p>';$mail->AltBody=$employee['first_name'].",\n\nPlease sign in to NPM Gateway and complete your Emergency Contact Information.\n\nKeeping this information current allows NPM to contact someone on your behalf during an emergency.\n\n{$link}";}
   $mail->isHTML(true);$mail->send();return true;
  }catch(\Throwable){return false;}
 }
}
