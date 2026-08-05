<?php

declare(strict_types=1);

$safeError = is_string($error ?? null) ? $error : '';
$safeUsername = is_string($username ?? null) ? $username : '';
$year = (new DateTimeImmutable())->format('Y');
$gatewayStylesheetPath = dirname(__DIR__, 3) . '/public/assets/css/gateway.css';
$gatewayStylesheetVersion = is_file($gatewayStylesheetPath) ? substr((string) hash_file('sha256', $gatewayStylesheetPath), 0, 12) : '1';
$passwordVisibilityPath = dirname(__DIR__, 3) . '/public/assets/js/password-visibility.js';
$passwordVisibilityVersion = is_file($passwordVisibilityPath) ? (string) filemtime($passwordVisibilityPath) : '1';
$processingOverlayPath = dirname(__DIR__, 3) . '/public/assets/js/processing-overlay.js';
$processingOverlayVersion = is_file($processingOverlayPath) ? (string) filemtime($processingOverlayPath) : '1';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — NPM Gateway</title>
    <link href="/assets/css/gateway.css?v=<?= rawurlencode($gatewayStylesheetVersion) ?>" rel="stylesheet">
</head>
<body class="gateway-auth-page">
<a class="gateway-skip-link" href="#main-content">Skip to sign in</a>
<main class="gateway-auth-main" id="main-content" tabindex="-1">
    <section class="gateway-auth-shell" aria-labelledby="gateway-auth-title">
        <div class="gateway-auth-brand">
            <div>
                <p class="gateway-auth-eyebrow">NPM Properties Inc.</p>
                <h1 id="gateway-auth-title">NPM Gateway</h1>
                <p class="gateway-auth-statement">Your connection to NPM operations.</p>
                <p class="gateway-auth-description">Secure access to company resources, community workflows, notifications, and operational tools.</p>
            </div>
            <p class="gateway-auth-internal">Internal use only</p>
        </div>
        <div class="gateway-auth-form-panel">
            <div class="gateway-auth-form">
                <header class="gateway-auth-form__header">
                    <h2>Sign in</h2>
                    <p>Enter your Gateway credentials to continue.</p>
                </header>
                <?php if ($safeError !== ''): ?>
                    <div class="alert gateway-alert gateway-alert--danger" role="alert" aria-live="assertive">
                        <?= htmlspecialchars($safeError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="/login" data-processing-form data-processing-message="Signing in…">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                    <div class="mb-4">
                        <label class="form-label" for="username">Gateway Username</label>
                        <input class="form-control" type="text" id="username" name="username" value="<?= htmlspecialchars($safeUsername, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" autocomplete="username" autocapitalize="none" spellcheck="false" aria-describedby="username-help" required autofocus>
                        <div class="form-text" id="username-help">Use your @gateway.npmparks.com username.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" aria-describedby="password-visibility" required>
                    </div>
                    <div class="form-check gateway-auth-password-toggle" id="password-visibility">
                        <input class="form-check-input" type="checkbox" id="show-password" data-password-visibility-control aria-controls="password">
                        <label class="form-check-label" for="show-password">Show password</label>
                    </div>
                    <button class="btn gateway-button gateway-button--primary gateway-auth-submit" type="submit">Sign in</button>
                </form>
                <aside class="gateway-auth-security">Authorized NPM personnel only.</aside>
            </div>
        </div>
    </section>
</main>
<footer class="gateway-auth-footer"><small>© <?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?> NPM Properties Inc.</small></footer>
<?php require dirname(__DIR__) . '/components/processing-overlay.php'; ?>
<script type="module" src="/assets/js/password-visibility.js?v=<?= rawurlencode($passwordVisibilityVersion) ?>"></script>
<script type="module" src="/assets/js/processing-overlay.js?v=<?= rawurlencode($processingOverlayVersion) ?>"></script>
</body>
</html>
