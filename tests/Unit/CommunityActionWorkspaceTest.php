<?php
declare(strict_types=1);

use NpmGateway\Services\CommunityActionProvider;
use PHPUnit\Framework\TestCase;

final class CommunityActionWorkspaceTest extends TestCase
{
    private const LABELS = ['Application Reviews','Credit Card Purchases','RM Corrections','Renovation Request','Request Appliances','Appliance Distribution','HVAC Service Request','Order Supplies','Eviction Checks','RM Audit'];
    private const SEGMENTS = ['application-reviews','credit-card-purchases','rm-corrections','renovation-requests','request-appliances','appliance-distribution','hvac-service-requests','order-supplies','eviction-checks','rm-audit'];

    public function testProviderIsTheOrderedAuthoritativePlannedCatalog(): void
    {
        $provider = new CommunityActionProvider();
        $actions = $provider->actions();
        self::assertCount(10, $actions);
        self::assertSame(self::LABELS, array_column($actions, 'label'));
        self::assertSame(self::SEGMENTS, array_column($actions, 'route_segment'));
        self::assertSame(range(1, 10), array_column($actions, 'order'));
        self::assertSame([true,true,false,false,false,false,false,false,false,false], array_column($actions, 'implemented'));
        foreach ($actions as $action) self::assertSame($action, $provider->findByRouteSegment($action['route_segment']));
        self::assertNull($provider->findByRouteSegment('unknown-action'));
    }

    public function testEveryActionHasAnAuthenticatedPropertyScopedRoute(): void
    {
        $routes = require dirname(__DIR__, 2).'/routes/web.php';
        foreach (self::SEGMENTS as $segment) {
            $path = '/community-actions/{propertySlug}/'.$segment;
            self::assertArrayHasKey($path, $routes);
            self::assertContains('GET', $routes[$path]['methods']);
            self::assertSame(['authentication'], $routes[$path]['middleware']);
        }
        $routeSource = (string) file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        self::assertStringNotContainsString('{propertyId}', $routeSource);
    }

    public function testWorkspaceAndPlaceholderShareProviderMetadataAndTrustedContext(): void
    {
        $root = dirname(__DIR__, 2);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/CommunityActionsController.php');
        $workspace = (string) file_get_contents($root.'/resources/views/community-actions/show.php');
        $placeholder = (string) file_get_contents($root.'/resources/views/community-actions/action.php');
        self::assertStringContainsString('$this->resolver->resolve', $controller);
        self::assertStringContainsString('$this->actions->actions()', $controller);
        self::assertStringContainsString('$this->actions->findByRouteSegment', $controller);
        foreach (['propertyDisplayName','propertySlug'] as $propertyContext) self::assertStringContainsString($propertyContext, $workspace.$placeholder);
        foreach (['breadcrumb.php','page-header.php','empty-state.php','Module planned'] as $component) self::assertStringContainsString($component, $placeholder);
        foreach (['<style','style=','<script','sidebar'] as $forbidden) self::assertStringNotContainsString($forbidden, $workspace.$placeholder);
    }

    public function testFutureFormIdentityFieldsAreAbsent(): void
    {
        $views = '';
        foreach (glob(dirname(__DIR__, 2).'/resources/views/community-actions/*.php') ?: [] as $file) $views .= (string) file_get_contents($file);
        self::assertStringNotContainsString('<form', $views);
        foreach (['name="property_id"','name="property_public_id"','name="property_slug"','name="employee_id"','name="user_id"','name="submitted_by"','name="manager_id"'] as $field) self::assertStringNotContainsString($field, $views);
    }

    public function testContextContainsOnlyServerResolvedIdentitiesAndVerifiedAccess(): void
    {
        $root = dirname(__DIR__, 2);
        $context = (string) file_get_contents($root.'/app/ValueObjects/CommunityActionContext.php');
        $resolver = (string) file_get_contents($root.'/app/Services/CommunityActionContextResolver.php');
        foreach (['userId','userPublicId','employeeId','employeePublicId','propertyId','propertyPublicId','propertySlug','propertyCode','propertyDisplayName','accessVerified'] as $field) self::assertStringContainsString($field, $context);
        self::assertStringContainsString('resolveAccessibleProperty', $resolver);
        self::assertStringContainsString('CommunityActionPropertyNotFoundException', $resolver);
        self::assertStringContainsString('CommunityActionPropertyForbiddenException', $resolver);
    }
}
