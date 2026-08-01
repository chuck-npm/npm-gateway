<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComponentRenderingTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function componentProvider(): iterable
    {
        foreach ([
            'header',
            'footer',
            'page-header',
            'card',
            'alert',
            'breadcrumb',
            'empty-state',
            'modal',
            'sidebar',
            'navbar',
            'pagination',
            'status-badge',
            'gateway-access-badge',
            'dashboard-card',
        ] as $component) {
            yield $component => [$component];
        }
    }

    #[DataProvider('componentProvider')]
    public function testDefaultComponentRenderingSucceeds(string $component): void
    {
        $output = $this->renderComponent($component);

        self::assertIsString($output);
    }

    public function testComponentOutputEscapesSuppliedText(): void
    {
        $unsafeText = '<script>alert("unsafe")</script>';
        $output = $this->renderComponent('alert', [
            'alertTitle' => $unsafeText,
            'alertMessage' => $unsafeText,
        ]);

        self::assertStringNotContainsString($unsafeText, $output);
        self::assertStringContainsString(
            '&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;',
            $output
        );
    }

    public function testTrustedHtmlSlotRendersControlledStaticMarkup(): void
    {
        $trustedMarkup = '<span data-showcase-marker="trusted">Controlled markup</span>';
        $output = $this->renderComponent('card', [
            'cardBodyHtml' => $trustedMarkup,
        ]);

        self::assertStringContainsString($trustedMarkup, $output);
    }

    #[DataProvider('gatewayAccessCases')]
    public function testGatewayAccessBadgeUsesApprovedAccessibleTextAndTreatment(string $state,string $label,string $type):void
    {
        $output=$this->renderComponent('gateway-access-badge',['gatewayAccessState'=>$state]);self::assertStringContainsString('gateway-status gateway-status--'.$type,$output);self::assertStringContainsString('>'.$label.'<',$output);self::assertStringNotContainsString('style=',$output);
    }
    public static function gatewayAccessCases():iterable{yield 'enabled'=>['enabled','Enabled','success'];yield 'disabled'=>['disabled','Disabled','warning'];yield 'none'=>['none','None','neutral'];yield 'untrusted'=>['<script>','None','neutral'];}

    /**
     * @param array<string, mixed> $variables
     */
    private function renderComponent(string $component, array $variables = []): string
    {
        extract($variables, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . $component . '.php';

        return (string) ob_get_clean();
    }
}
