<?php
declare(strict_types=1);
namespace NpmGateway\Console;
use NpmGateway\Services\HrEmployeeNotificationConfig;
final class NotificationCheckCommand
{
    public static function run(array $config):array
    {
        $environment=(string)($config['environment']??'production');if(!in_array($environment,['local','development','testing'],true))return ['exit_code'=>2,'stdout'=>'','stderr'=>"Notification diagnostics are restricted to local/testing environments.\n"];
        try{$resolved=HrEmployeeNotificationConfig::fromArray($config);$available=class_exists(\PHPMailer\PHPMailer\PHPMailer::class);$lines=['HR notification configuration: '.($available?'ready':'blocked'),'PHPMailer: '.($available?'available':'unavailable'),'Sender: '.$resolved->fromAddress,'Sender name: '.$resolved->fromName,'Recipient count: '.count($resolved->recipients),'SMTP host: '.$resolved->host,'SMTP port: '.$resolved->port,'SMTP secure mode: '.$resolved->secure,'Email sent: no'];return ['exit_code'=>$available?0:1,'stdout'=>implode("\n",$lines)."\n",'stderr'=>''];}catch(\Throwable){$rawRecipients=array_filter(array_map('trim',explode(',',(string)($config['recipients']??''))));$lines=['HR notification configuration: blocked','PHPMailer: '.(class_exists(\PHPMailer\PHPMailer\PHPMailer::class)?'available':'unavailable'),'Sender: '.((string)($config['from_address']??'')?:'missing'),'Sender name: '.((string)($config['from_name']??'')?:'missing'),'Recipient count: '.count(array_unique($rawRecipients)),'SMTP host: '.((string)($config['host']??'')?:'missing'),'SMTP port: '.((string)($config['port']??'')?:'missing'),'SMTP secure mode: '.((string)($config['secure']??'')?:'missing'),'Email sent: no'];return ['exit_code'=>1,'stdout'=>implode("\n",$lines)."\n",'stderr'=>"Notification configuration check failed.\n"];}
    }
}
