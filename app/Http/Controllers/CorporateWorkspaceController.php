<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\ValueObjects\ToolCard;
final class CorporateWorkspaceController
{
 public function __construct(private readonly CorporateAccessService $access,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly string $views){}
 public function operations(AuthenticatedRequestContext $context):Response{if(!$this->allowed($context,'operations'))return new Response(403,'Forbidden');$cards=[new ToolCard('inventory-cf','Inventory – CF','Warehouse inventory for Crumley Farms.','Operations','Open Inventory – CF','/corporate/operations/inventory-cf',true,10,null,null,'corporate.operations.inventory-cf'),new ToolCard('inventory-hr','Inventory – HR','Warehouse inventory for Highridge.','Operations','Open Inventory – HR','/corporate/operations/inventory-hr',true,20,null,null,'corporate.operations.inventory-hr'),new ToolCard('development','Development','Development projects, planning, and related operational work.','Operations','Open Development','/corporate/operations/development',true,30,null,null,'corporate.operations.development')];return $this->render($context,'corporate/operations/index.php',compact('cards'));}
 public function inventoryCf(AuthenticatedRequestContext $context):Response{return $this->planned($context,'operations','Inventory – CF','The Crumley Farms warehouse inventory workspace will be implemented here.','/corporate/operations');}
 public function inventoryHr(AuthenticatedRequestContext $context):Response{return $this->planned($context,'operations','Inventory – HR','The Highridge warehouse inventory workspace will be implemented here.','/corporate/operations');}
 public function development(AuthenticatedRequestContext $context):Response{return $this->planned($context,'operations','Development','Development project planning and tracking will be implemented here.','/corporate/operations');}
 public function marketing(AuthenticatedRequestContext $context):Response{if(!$this->allowed($context,'marketing'))return new Response(403,'Forbidden');$cards=[new ToolCard('flyers','Flyers','Create, organize, and manage company and property flyers.','Marketing','Open Flyers','/corporate/marketing/flyers',true,10,null,null,'corporate.marketing.flyers')];return $this->render($context,'corporate/marketing/index.php',compact('cards'));}
 public function flyers(AuthenticatedRequestContext $context):Response{return $this->planned($context,'marketing','Flyers','Flyer creation and management will be implemented here.','/corporate/marketing');}
 private function planned(AuthenticatedRequestContext $context,string $category,string $heading,string $message,string $parent):Response{if(!$this->allowed($context,$category))return new Response(403,'Forbidden');return $this->render($context,'corporate/planned.php',compact('category','heading','message','parent'));}
 private function allowed(AuthenticatedRequestContext $context,string $category):bool{return $this->access->canAccessCategory($context,$category);}
 private function render(AuthenticatedRequestContext $context,string $view,array $variables=[]):Response{$user=$context->user;$logoutCsrfToken=$this->csrf->token();$navbarCorporateItems=$this->tools->tools($context);extract($variables,EXTR_SKIP);ob_start();require $this->views.'/'.$view;return new Response(200,(string)ob_get_clean());}
}
