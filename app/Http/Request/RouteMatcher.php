<?php

declare(strict_types=1);

namespace NpmGateway\Http\Request;

final class RouteMatcher
{
    /**
     * @param array<string, array{view: string, environments?: list<string>}> $routes
     * @return array{view: string, environments?: list<string>}|null
     */
    public function match(string $path, string $environment, array $routes): ?array
    {
        $route = $routes[$path] ?? null;

        if ($route === null) {
            return null;
        }

        $allowedEnvironments = $route['environments'] ?? [];

        if ($allowedEnvironments !== [] && !in_array($environment, $allowedEnvironments, true)) {
            return null;
        }

        return $route;
    }
}
