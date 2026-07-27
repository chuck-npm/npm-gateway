<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Repositories\LoginAttemptRepository;
final class LoginThrottleService
{
 public function __construct(private readonly LoginAttemptRepository $attempts,private readonly AuthenticationConfig $config){}
 public function limited(string $ipHash,\DateTimeImmutable $now):bool
 {
  return $this->attempts->countRecentFailuresByIp($ipHash,$now->modify("-{$this->config->ipWindowMinutes} minutes")->format('Y-m-d H:i:s')) >= $this->config->ipFailureLimit;
 }
}
