<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Contracts\CompanyAnnouncementEmailSenderInterface;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\Services\EmployeeAnnouncementEmailBuilder;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
use PHPMailer\PHPMailer\PHPMailer;
final class CompanyAnnouncementEmailSender implements CompanyAnnouncementEmailSenderInterface
{
 public function __construct(private readonly HrEmployeeNotificationConfig $config,private readonly EmployeeAnnouncementEmailBuilder $builder,private readonly CompanyAnnouncementEmailRenderer $renderer,private readonly ?\Closure $factory=null){}
 public function send(string $email,EmployeeAnnouncement $a):bool
 {
  try{$content=$this->renderer->render($this->builder->build($a));$mail=$this->factory?($this->factory)():new PHPMailer(true);$mail->isSMTP();$mail->Host=$this->config->host;$mail->Port=$this->config->port;$mail->SMTPAuth=true;$mail->Username=$this->config->username;$mail->Password=$this->config->password;$mail->SMTPSecure=$this->config->secure==='ssl'?PHPMailer::ENCRYPTION_SMTPS:PHPMailer::ENCRYPTION_STARTTLS;$mail->CharSet=PHPMailer::CHARSET_UTF8;$mail->setFrom($this->config->fromAddress,$this->config->fromName);$mail->addAddress($email);$mail->Subject='New Employee — '.$a->payload['employee_name'];$mail->isHTML(true);$mail->Body=$content['html'];$mail->AltBody=$content['text'];$mail->send();return true;}catch(\Throwable){return false;}
 }
}
