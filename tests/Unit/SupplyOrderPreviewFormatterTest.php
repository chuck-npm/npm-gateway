<?php

declare(strict_types=1);

namespace Tests\Unit;

use NpmGateway\Services\RmAuditRichTextSanitizer;
use NpmGateway\Support\SupplyOrderPreviewFormatter;
use PHPUnit\Framework\TestCase;

final class SupplyOrderPreviewFormatterTest extends TestCase
{
    private SupplyOrderPreviewFormatter $formatter;
    private RmAuditRichTextSanitizer $sanitizer;

    protected function setUp(): void
    {
        $this->formatter = new SupplyOrderPreviewFormatter();
        $this->sanitizer = new RmAuditRichTextSanitizer();
    }

    public function testParagraphsListsAndMixedBlocksUseReadableSeparators(): void
    {
        self::assertSame(
            '2 cases of copy paper, toner for Brother L2750 printer, 1 box black ink pens',
            $this->preview('<p>2 cases of copy paper</p><p>toner for Brother L2750 printer</p><p>1 box black ink pens</p>')
        );
        self::assertSame(
            'Copy paper, Black pens, AAA batteries',
            $this->preview('<ul><li>Copy paper</li><li>Black pens</li><li>AAA batteries</li></ul>')
        );
        self::assertSame(
            'Office, Copy paper, Black pens, Done',
            $this->preview('<p>Office</p><ul><li>Copy paper</li><li>Black pens</li></ul><p>Done</p>')
        );
    }

    public function testBlankBlocksWhitespaceAndExistingCommasNormalizeWithoutDuplicates(): void
    {
        self::assertSame(
            'Air filters, 20x20, 2 cases, Copy paper',
            $this->preview("<p> Air   filters </p><p><br></p><p>20x20,  2 cases</p><p>Copy paper</p>")
        );
        self::assertSame('First line, Second line', $this->preview('<p>First line<br>Second line</p>'));
        self::assertStringNotContainsString(',,', $this->preview('<p>20x20 filters, 2 cases</p><p>Copy paper</p>'));
    }

    public function testPreviewContainsReadableLinkTextButNoMarkupOrRemovedUnsafeContent(): void
    {
        $preview = $this->preview('<p><a href="https://vendor.example/item">Safe product</a></p><script>unsafe()</script><img src="x"><p>Paper</p>');
        self::assertSame('Safe product, Paper', $preview);
        self::assertStringNotContainsString('<a', $preview);
        self::assertStringNotContainsString('<p', $preview);
        self::assertStringNotContainsString('unsafe', $preview);
    }

    public function testNormalizationPrecedesTheExistingPreviewTruncation(): void
    {
        $first = str_repeat('A', 175);
        $normalized = $first.', Second block';
        $preview = $this->preview('<p>'.$first.'</p><p>Second block</p>');

        self::assertSame(mb_strimwidth($normalized, 0, 180, '…'), $preview);
        self::assertSame(180, mb_strwidth($preview));
        self::assertStringContainsString(', Se…', $preview);
    }

    public function testHistoryAloneUsesFormatterWhileDetailAndEmailKeepCanonicalFields(): void
    {
        $root = dirname(__DIR__, 2);
        $history = (string) file_get_contents($root.'/resources/views/community-actions/supply-orders/index.php');
        $detail = (string) file_get_contents($root.'/resources/views/community-actions/supply-orders/show.php');
        $email = (string) file_get_contents($root.'/app/Notifications/SupplyOrderEmailSender.php');

        self::assertStringContainsString("\$preview->format(\$order['request_html'])", $history);
        self::assertStringContainsString("\$order['request_html']", $detail);
        self::assertStringContainsString("'trusted_sanitized_html'=>\$order['request_html']", $email);
        self::assertStringContainsString("'plain_text'=>\$order['request_text']", $email);
        self::assertStringNotContainsString('SupplyOrderPreviewFormatter', $detail.$email);
    }

    private function preview(string $html): string
    {
        return $this->formatter->format($this->sanitizer->sanitize($html)['html']);
    }
}
