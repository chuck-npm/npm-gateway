<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ComponentShowcaseRouteTest extends TestCase
{
    public function testShowcaseRouteSucceedsInLocalEnvironment(): void
    {
        $response = $this->renderShowcase('local');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Component Showcase — Development Only', $response['body']);
        self::assertStringContainsString('Gateway component showcase', $response['body']);
        self::assertStringContainsString('id="showcase-confirmation"', $response['body']);
    }

    public function testShowcaseRouteSucceedsInDevelopmentEnvironment(): void
    {
        $response = $this->renderShowcase('development');

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Component Showcase — Development Only', $response['body']);
    }

    public function testShowcaseRouteIsNotFoundOutsideDevelopmentEnvironments(): void
    {
        $response = $this->renderShowcase('production');

        self::assertSame(404, $response['status']);
        self::assertStringNotContainsString('Component Showcase — Development Only', $response['body']);
        self::assertStringContainsString('The requested resource could not be found.', $response['body']);
    }

    /**
     * @return array{status: int, body: string}
     */
    private function renderShowcase(string $environment): array
    {
        $previousEnvironment = getenv('APP_ENV');
        $previousServerEnvironment = $_ENV['APP_ENV'] ?? null;
        $previousRequestUri = $_SERVER['REQUEST_URI'] ?? null;

        putenv('APP_ENV=' . $environment);
        $_ENV['APP_ENV'] = $environment;
        $_SERVER['REQUEST_URI'] = '/component-showcase';
        http_response_code(200);

        ob_start();
        include dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
        $body = (string) ob_get_clean();
        $status = $responseStatus;

        $previousEnvironment === false
            ? putenv('APP_ENV')
            : putenv('APP_ENV=' . $previousEnvironment);

        if ($previousServerEnvironment === null) {
            unset($_ENV['APP_ENV']);
        } else {
            $_ENV['APP_ENV'] = $previousServerEnvironment;
        }

        if ($previousRequestUri === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $previousRequestUri;
        }

        return [
            'status' => $status,
            'body' => $body,
        ];
    }
}
