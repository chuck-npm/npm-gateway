<?php

declare(strict_types=1);

namespace NpmGateway\Support;

final class SupplyOrderPreviewFormatter
{
    public const MAX_WIDTH = 180;

    public function format(string $sanitizedHtml): string
    {
        $separated = (string) preg_replace('/<br\s*\/?>|<\/p\s*>|<\/li\s*>/iu', ', ', $sanitizedHtml);
        $text = html_entity_decode(strip_tags($separated), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/\s+/u', ' ', $text);
        $text = (string) preg_replace('/(?:\s*,\s*)+/u', ', ', $text);
        $text = trim($text, " \t\n\r\0\x0B,");

        return mb_strimwidth($text, 0, self::MAX_WIDTH, '…');
    }
}
