<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final readonly class StorageConfiguration
{
 public function __construct(public string $endpoint,public string $region,public string $container,public string $accessKey,public string $secretKey,public string $attachmentPrefix,public string $imagePrefix,public string $testPrefix)
 {
  $host=parse_url($endpoint,PHP_URL_HOST);if(parse_url($endpoint,PHP_URL_SCHEME)!=='https'||!is_string($host)||$host===''||parse_url($endpoint,PHP_URL_USER)!==null||parse_url($endpoint,PHP_URL_PASS)!==null)throw new \InvalidArgumentException('Storage endpoint configuration is invalid.');foreach(['region'=>$region,'container'=>$container,'access key'=>$accessKey,'secret key'=>$secretKey] as $label=>$value)if(trim($value)==='')throw new \InvalidArgumentException("Storage {$label} is missing.");foreach([$attachmentPrefix,$imagePrefix,$testPrefix] as $prefix)$this->validatePrefix($prefix);if($attachmentPrefix===$imagePrefix)throw new \InvalidArgumentException('Storage attachment and image prefixes must differ.');
 }
 public static function fromArray(array $config):self{return new self(rtrim((string)($config['endpoint']??''),'/'),(string)($config['region']??''),(string)($config['container']??''),(string)($config['access_key']??''),(string)($config['secret_key']??''),(string)($config['attachment_prefix']??''),(string)($config['image_prefix']??''),(string)($config['test_prefix']??''));}
 public function endpointHost():string{return (string)parse_url($this->endpoint,PHP_URL_HOST);}
 private function validatePrefix(string $prefix):void{if($prefix===''||str_starts_with($prefix,'/')||!str_ends_with($prefix,'/')||str_ends_with($prefix,'//')||str_contains($prefix,'\\')||str_contains($prefix,'..')||str_contains($prefix,"\0")||str_contains($prefix,'://')||!preg_match('#^[A-Za-z0-9/_-]+/$#D',$prefix))throw new \InvalidArgumentException('Storage prefix configuration is invalid.');}
}
