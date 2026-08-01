<?php
declare(strict_types=1);
$gatewayAccessState=isset($gatewayAccessState)?strtolower((string)$gatewayAccessState):'none';
[$statusLabel,$statusType]=match($gatewayAccessState){
    'enabled'=>['Enabled','success'],
    'disabled'=>['Disabled','warning'],
    default=>['None','neutral'],
};
require __DIR__.'/status-badge.php';
?>
