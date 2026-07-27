<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in — NPM Gateway</title><link href="/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"><link href="/assets/css/gateway.css" rel="stylesheet"></head>
<body><main class="container py-5" style="max-width:32rem"><section class="gateway-panel"><h1>NPM Gateway</h1><p>Sign in to continue</p>
<?php if(is_string($error??null)&&$error!==''):?><div class="alert alert-danger" role="alert"><?=htmlspecialchars($error,ENT_QUOTES,'UTF-8')?></div><?php endif?>
<form method="post" action="/login"><input type="hidden" name="_token" value="<?=htmlspecialchars($csrfToken,ENT_QUOTES,'UTF-8')?>">
<div class="mb-3"><label class="form-label" for="username">Username</label><input class="form-control" id="username" name="username" autocomplete="username" required></div>
<div class="mb-3"><label class="form-label" for="password">Password</label><input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required></div>
<button class="btn btn-primary" type="submit">Sign In</button></form></section></main></body></html>
