<?php
declare(strict_types=1);
$components=dirname(__DIR__,2).'/components';ob_start();$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Human Resources','url'=>'/human-resources'],['label'=>'Properties','url'=>'/human-resources/properties'],['label'=>'Add Property','current'=>true]];require $components.'/breadcrumb.php';$heading='Add Property';$description='Create a new company property and operational context.';$eyebrow='Human Resources';$actionsHtml='';$pageHeaderSpacious=true;require $components.'/page-header.php';
?><div class="gateway-card gateway-property-form-card"><div class="gateway-card__body"><?php require __DIR__.'/_form.php'; ?></div></div><?php
$contentHtml=(string)ob_get_clean();$pageTitle='Add Property — NPM Gateway';$navbarItems=\NpmGateway\Support\Navigation::forRoute('/human-resources/properties/create',dirname(__DIR__,4));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username;require dirname(__DIR__,2).'/layouts/app.php';
