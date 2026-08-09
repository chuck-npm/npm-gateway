<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SupplyOrderBreadcrumbPresentationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testAllSupplyOrderPagesUseTheSharedPropertyScopedBreadcrumbHierarchy(): void
    {
        $views = [
            'index' => ['Supply Orders', false],
            'create' => ['Order Supplies', true],
            'show' => ['Supply Order', true],
        ];

        foreach ($views as $name => [$currentLabel, $linksHistory]) {
            $view = $this->view($name);
            self::assertStringContainsString("['label' => 'Dashboard', 'url' => '/dashboard']", $view);
            self::assertStringContainsString("['label' => 'Community Actions', 'url' => '/community-actions']", $view);
            self::assertStringContainsString("['label' => \$context->propertyDisplayName, 'url' => '/community-actions/'.\$context->propertySlug]", $view);
            self::assertStringContainsString("['label' => '".$currentLabel."', 'current' => true]", $view);
            self::assertSame($linksHistory, str_contains($view, "['label' => 'Supply Orders', 'url' => '/community-actions/'.\$context->propertySlug.'/supply-orders']"));
            self::assertStringContainsString("require \$components.'/breadcrumb.php'", $view);
            self::assertStringNotContainsString('propertyId', $view);
            self::assertStringNotContainsString('property_id', $view);
        }
    }

    public function testSharedBreadcrumbRendersDynamicPropertyRoutesAndNonClickableCurrentItem(): void
    {
        foreach ([['Pine Hill', 'pine-hill'], ['Highridge', 'highridge']] as [$name, $slug]) {
            $breadcrumbItems = [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Community Actions', 'url' => '/community-actions'],
                ['label' => $name, 'url' => '/community-actions/'.$slug],
                ['label' => 'Supply Orders', 'url' => '/community-actions/'.$slug.'/supply-orders'],
                ['label' => 'Order Supplies', 'current' => true],
            ];
            ob_start();
            require $this->root.'/resources/views/components/breadcrumb.php';
            $html = (string) ob_get_clean();

            foreach (['/dashboard', '/community-actions', '/community-actions/'.$slug, '/community-actions/'.$slug.'/supply-orders'] as $url) {
                self::assertStringContainsString('href="'.$url.'"', $html);
            }
            self::assertStringContainsString($name, $html);
            self::assertMatchesRegularExpression('/<li[^>]*aria-current="page"[^>]*>\s*Order Supplies\s*<\/li>/', $html);
            self::assertStringNotContainsString('href="/community-actions/'.$slug.'/supply-orders/create"', $html);
        }
    }

    public function testDetailBackActionAndResolvedPropertyAccessRemainUnchanged(): void
    {
        self::assertStringContainsString('>Back to Supply Orders</a>', $this->view('show'));
        $controller = (string) file_get_contents($this->root.'/app/Http/Controllers/SupplyOrderController.php');
        self::assertStringContainsString('$this->resolver->resolve($c,$slug)', $controller);
        self::assertStringContainsString('detailForProperty($public,$context->propertyId)', $controller);
    }

    private function view(string $name): string
    {
        return (string) file_get_contents($this->root.'/resources/views/community-actions/supply-orders/'.$name.'.php');
    }
}
