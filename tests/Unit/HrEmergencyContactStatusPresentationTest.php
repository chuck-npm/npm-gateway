<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HrEmergencyContactStatusPresentationTest extends TestCase
{
    public function testCompletedUsesStandardSuccessBadgeAndMissingRemainsPlainText(): void
    {
        $view = (string) file_get_contents(
            dirname(__DIR__, 2) . '/resources/views/human-resources/emergency-contacts/index.php'
        );

        self::assertStringContainsString('$statusLabel=\'Completed\'', $view);
        self::assertStringContainsString('$statusType=\'success\'', $view);
        self::assertStringContainsString("require \$components.'/status-badge.php'", $view);
        self::assertMatchesRegularExpression('/else:\?>Missing<\?php endif;/', $view);

        foreach (['danger', 'warning', 'error'] as $forbiddenStyle) {
            self::assertDoesNotMatchRegularExpression(
                '/Missing.{0,80}' . $forbiddenStyle . '/is',
                $view
            );
        }
    }
}
