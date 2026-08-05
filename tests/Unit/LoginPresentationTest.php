<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class LoginPresentationTest extends TestCase
{
    private string $view;
    private string $css;

    protected function setUp(): void
    {
        $root = dirname(__DIR__, 2);
        $this->view = (string) file_get_contents($root . '/resources/views/auth/login.php');
        $this->css = (string) file_get_contents($root . '/public/assets/css/gateway.css');
    }

    public function testAuthenticationContractAndPrivacyMarkupRemainIntact(): void
    {
        foreach (['method="post" action="/login"', 'name="_token"', 'name="username"', 'name="password"', 'autocomplete="username"', 'autocomplete="current-password"'] as $required) self::assertStringContainsString($required, $this->view);
        self::assertStringNotContainsString('value="<?= htmlspecialchars($safePassword', $this->view);
        self::assertStringNotContainsString('register', strtolower($this->view));
        self::assertStringNotContainsString('style=', $this->view);
        self::assertStringNotContainsString('<script>', $this->view);
    }

    public function testProfessionalIdentityAndAccessibleFormRender(): void
    {
        foreach (['NPM Properties Inc.', 'NPM Gateway', 'Your connection to NPM operations.', 'Sign in', 'Gateway Username', 'Password', 'Show password', 'Authorized NPM personnel only.', 'data-processing-form', 'Signing in…'] as $text) self::assertStringContainsString($text, $this->view);
        self::assertStringNotContainsString('Forgot your password?', $this->view);
        self::assertStringNotContainsString('gateway-auth-forgot', $this->view . $this->css);
        foreach (['gateway-auth-page', 'gateway-auth-shell', 'gateway-auth-brand', 'gateway-auth-form', 'gateway-auth-footer'] as $class) self::assertStringContainsString($class, $this->view);
        foreach (['dashboard', 'Corporate', 'Notifications', 'Community Actions', 'gateway-navbar'] as $navigation) self::assertStringNotContainsString($navigation, $this->view);
    }

    public function testResponsiveSplitAndStackedContractsAreScoped(): void
    {
        self::assertStringContainsString('grid-template-columns:minmax(0,42fr) minmax(0,58fr)', $this->css);
        self::assertMatchesRegularExpression('/@media \(max-width:767\.98px\).*?\.gateway-auth-shell \{ min-height:0;grid-template-columns:1fr;/s', $this->css);
        self::assertStringContainsString('background:var(--gateway-navy);border-right:2px solid var(--gateway-gold)', $this->css);
        self::assertStringContainsString('border-right:0;border-bottom:2px solid var(--gateway-gold)', $this->css);
    }

    public function testFinalAuthSpacingAndControlPolishRemainScoped(): void
    {
        self::assertStringContainsString('.gateway-auth-form .form-text { margin-top:.7rem;', $this->css);
        self::assertStringContainsString('.gateway-auth-password-toggle { display:flex;gap:.55rem;align-items:center;', $this->css);
        self::assertStringContainsString('.gateway-auth-password-toggle .form-check-input { flex:0 0 auto;', $this->css);
        self::assertStringContainsString('.gateway-auth-password-toggle .form-check-label { line-height:1.25; }', $this->css);
        self::assertStringContainsString('.gateway-auth-submit { display:block;width:100%;min-height:3rem;padding:.8rem 1rem;', $this->css);
        self::assertStringContainsString('.gateway-auth-security { margin-top:1.5rem;padding-top:1.25rem;', $this->css);
        self::assertStringContainsString('aria-describedby="username-help"', $this->view);
        self::assertStringContainsString('type="checkbox" id="show-password"', $this->view);
        self::assertStringContainsString('for="show-password">Show password</label>', $this->view);
        self::assertStringContainsString('data-processing-message="Signing in…"', $this->view);
    }

    public function testAuthoritativeStylesheetUsesRootAbsoluteCacheBustedUrl(): void
    {
        self::assertStringContainsString("dirname(__DIR__, 3) . '/public/assets/css/gateway.css'", $this->view);
        self::assertStringContainsString('href="/assets/css/gateway.css?v=', $this->view);
        self::assertStringNotContainsString('href="assets/css/gateway.css', $this->view);
        self::assertStringNotContainsString('bootstrap', strtolower($this->view));
        self::assertStringContainsString('<meta name="viewport"', $this->view);
    }
}
