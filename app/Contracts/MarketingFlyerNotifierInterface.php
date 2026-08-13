<?php declare(strict_types=1);namespace NpmGateway\Contracts;interface MarketingFlyerNotifierInterface{public function send(array$flyer,array$recipients,string$localPath):array;}
