<?php
declare(strict_types=1);
$components=dirname(__DIR__,2).'/components';ob_start();
$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Human Resources','url'=>'/human-resources'],['label'=>'Properties','url'=>'/human-resources/properties'],['label'=>(string)$property['display_name'],'url'=>'/human-resources/properties'],['label'=>'Edit','current'=>true]];require$components.'/breadcrumb.php';
$heading='Edit '.(string)$property['display_name'];$description='Update property identity, location, and operational contact information.';$eyebrow='Human Resources';$actionsHtml='';$pageHeaderSpacious=true;require$components.'/page-header.php';
$formAction='/human-resources/properties/'.rawurlencode((string)$property['public_id']).'/edit';$submitLabel='Save Changes';$validationHeading='We could not update the property.';?><div class="gateway-card gateway-property-form-card"><div class="gateway-card__body"><?php require __DIR__.'/_form.php';?></div></div><?php
$contentHtml=(string)ob_get_clean();$pageTitle='Edit Property — NPM Gateway';$navbarItems=\NpmGateway\Support\Navigation::forRoute('/human-resources/properties',dirname(__DIR__,4));$navbarUserLabel=$user->displayName;$navbarUserContext='@'.$user->username;require dirname(__DIR__,2).'/layouts/app.php';
