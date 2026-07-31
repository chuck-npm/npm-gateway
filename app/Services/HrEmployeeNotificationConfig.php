<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
final readonly class HrEmployeeNotificationConfig
{
    public function __construct(public string $host,public int $port,public string $username,#[\SensitiveParameter] public string $password,public string $secure,public string $fromAddress,public string $fromName,public array $recipients,public string $environment){}
    public static function fromArray(array $config):self
    {
        $host=trim((string)($config['host']??''));if($host==='')throw new CredentialNotificationException('SMTP host is not configured.');$port=filter_var($config['port']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);if($port===false)throw new CredentialNotificationException('SMTP port configuration is invalid.');$secure=strtolower(trim((string)($config['secure']??'')));if(!in_array($secure,['tls','ssl'],true))throw new CredentialNotificationException('Encrypted SMTP transport is required.');$username=trim((string)($config['username']??''));$password=(string)($config['password']??'');if($username===''||$password==='')throw new CredentialNotificationException('Authenticated SMTP credentials are not configured.');$from=strtolower(trim((string)($config['from_address']??'')));if($from!=='no-reply@npmpropertiesinc.com'||filter_var($from,FILTER_VALIDATE_EMAIL)===false)throw new CredentialNotificationException('The approved HR notification sender is not configured.');$name=trim((string)($config['from_name']??''));if($name==='')throw new CredentialNotificationException('HR notification sender name is not configured.');$raw=array_map('trim',explode(',',(string)($config['recipients']??'')));$recipients=[];foreach($raw as $email){if($email===''||filter_var($email,FILTER_VALIDATE_EMAIL)===false)throw new CredentialNotificationException('HR notification recipient configuration is invalid.');if(!in_array($email,$recipients,true))$recipients[]=$email;}if($recipients===[])throw new CredentialNotificationException('HR notification recipients are not configured.');return new self($host,(int)$port,$username,$password,$secure,$from,$name,$recipients,(string)($config['environment']??'production'));
    }
}
