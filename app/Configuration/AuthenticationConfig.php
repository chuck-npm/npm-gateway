<?php
declare(strict_types=1);
namespace NpmGateway\Configuration;
final readonly class AuthenticationConfig
{
    public function __construct(
        public string $cookieName, public bool $secure, public bool $httpOnly, public string $sameSite,
        public int $idleMinutes, public int $absoluteHours, public int $rotationMinutes,
        public int $activityWriteMinutes, public int $maxFailures, public int $lockMinutes,
        public int $ipFailureLimit, public int $ipWindowMinutes, private string $appKey
    ) {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]{1,63}$/', $cookieName)) throw new \InvalidArgumentException('Invalid session cookie name.');
        if (!in_array($sameSite, ['Lax', 'Strict'], true)) throw new \InvalidArgumentException('Unsupported SameSite policy.');
        if ($idleMinutes < 5 || $absoluteHours * 60 <= $idleMinutes || $rotationMinutes < 1 || $rotationMinutes >= $idleMinutes || $activityWriteMinutes < 1) throw new \InvalidArgumentException('Invalid session time policy.');
        if (min($maxFailures, $lockMinutes, $ipFailureLimit, $ipWindowMinutes) < 1) throw new \InvalidArgumentException('Invalid authentication policy.');
        if (strlen($appKey) < 32) throw new \InvalidArgumentException('APP_KEY must contain at least 32 characters.');
    }
    public function deriveKey(string $domain): string { return hash_hmac('sha256', $domain, $this->appKey, true); }
}
