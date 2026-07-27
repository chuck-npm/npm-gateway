<?php
declare(strict_types=1);
namespace NpmGateway\Support;
use NpmGateway\Contracts\PasswordGeneratorInterface;
final class SecurePasswordGenerator implements PasswordGeneratorInterface
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*-_=+';
    public function __construct(private readonly int $length = 32)
    {
        if ($length < 24) { throw new \InvalidArgumentException('Password length must be at least 24.'); }
    }
    public function generate(): string
    {
        $password = '';
        $maximum = strlen(self::ALPHABET) - 1;
        for ($index = 0; $index < $this->length; $index++) {
            $password .= self::ALPHABET[random_int(0, $maximum)];
        }
        return $password;
    }
}
