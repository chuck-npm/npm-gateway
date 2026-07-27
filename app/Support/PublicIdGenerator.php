<?php
declare(strict_types=1);
namespace NpmGateway\Support;
final class PublicIdGenerator
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    public function generate(): string
    {
        $milliseconds = (int) floor(microtime(true) * 1000);
        $value = '';
        for ($index = 9; $index >= 0; $index--) {
            $value = self::ALPHABET[$milliseconds % 32] . $value;
            $milliseconds = intdiv($milliseconds, 32);
        }
        for ($index = 0; $index < 16; $index++) { $value .= self::ALPHABET[random_int(0, 31)]; }
        return $value;
    }
}
