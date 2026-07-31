<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Contracts\HrEmployeeNotifierInterface;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\Services\HrEmployeeNotificationConfig;
use NpmGateway\ValueObjects\HrEmployeeNotice;
use PHPMailer\PHPMailer\PHPMailer;
final class SmtpEmployeeNotificationSender implements HrEmployeeNotifierInterface
{
    public function __construct(private readonly HrEmployeeNotificationConfig $config,private readonly ?\Closure $factory=null){}
    public function notify(HrEmployeeNotice $notice):void
    {
        try{$mail=$this->factory!==null?($this->factory)():new PHPMailer(true);if(!$mail instanceof PHPMailer)throw new \RuntimeException('Invalid mail transport factory.');$mail->isSMTP();$mail->SMTPDebug=0;$mail->Host=$this->config->host;$mail->Port=$this->config->port;$mail->SMTPAuth=true;$mail->Username=$this->config->username;$mail->Password=$this->config->password;$mail->SMTPSecure=$this->config->secure==='ssl'?PHPMailer::ENCRYPTION_SMTPS:PHPMailer::ENCRYPTION_STARTTLS;$mail->CharSet=PHPMailer::CHARSET_UTF8;$mail->setFrom($this->config->fromAddress,$this->config->fromName);foreach($this->config->recipients as $recipient)$mail->addAddress($recipient);$mail->Subject=$notice->subject;$mail->isHTML(true);$mail->Body=$notice->htmlBody;$mail->AltBody=$notice->textBody;$mail->send();}catch(\Throwable $e){throw new CredentialNotificationException('The secure employee notification could not be sent.',0,$e);}
    }
}
