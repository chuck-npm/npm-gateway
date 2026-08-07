<?php
declare(strict_types=1);

$components=dirname(__DIR__).'/components';
ob_start();
$breadcrumbItems=[['label'=>'Dashboard','url'=>'/dashboard'],['label'=>'Community Actions','current'=>true]];
require $components.'/breadcrumb.php';
$heading='Community Actions';
$description='Select a community to access property management tasks and operational activities.';
$eyebrow='Universal Tools';
$actionsHtml='';
require $components.'/page-header.php';
if($properties===[]):
    $emptyTitle='No communities are available.';
    $emptyMessage='You do not currently have access to any Community Actions workspaces.';
    $emptyIconHtml='';
    $emptyActionHtml='';
    require $components.'/empty-state.php';
else:
?>
<div class="gateway-tool-grid">
<?php foreach($properties as $property):
    $toolCard=new \NpmGateway\ValueObjects\ToolCard('community-'.$property['slug'],$property['display_name'],'Open community actions and operational tools.',$property['property_code'],'Open '.$property['display_name'],'/community-actions/'.$property['slug'],true,10,null,'Open '.$property['display_name'],'community-actions.show');
    require $components.'/tool-card.php';
endforeach; ?>
</div>
<?php
endif;
$contentHtml=(string)ob_get_clean();
$pageTitle='Community Actions — NPM Gateway';
$navbarItems=\NpmGateway\Support\Navigation::forRoute('/community-actions',dirname(__DIR__,3));
$navbarUserLabel=$user->displayName;
$navbarUserContext='@'.$user->username;
$footerText='NPM Gateway — Internal use only';
require dirname(__DIR__).'/layouts/app.php';
