<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\PasswordGeneratorInterface;
use NpmGateway\ValueObjects\GeneratedCredential;
final class PasswordService
{
    public function __construct(private readonly PasswordGeneratorInterface $generator) {}
    public function generate(): GeneratedCredential
    {
        $plaintext = $this->generator->generate();
        if (strlen($plaintext) < 24) { throw new \RuntimeException('Generated credential did not meet security policy.'); }
        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $hash = password_hash($plaintext, $algorithm);
        if (!is_string($hash)) { throw new \RuntimeException('Unable to securely hash generated credential.'); }
        $info = password_get_info($hash);
        return new GeneratedCredential($plaintext, $hash, (string) ($info['algoName'] ?? 'unknown'));
    }
}
