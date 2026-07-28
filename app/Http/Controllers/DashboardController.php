<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\DashboardHomeService;
final class DashboardController
{
 public function __construct(private readonly CsrfService $csrf,private readonly DashboardHomeService $homes,private readonly string $views){}
 public function index(AuthenticatedRequestContext $context):Response{$user=$context->user;$csrfToken=$this->csrf->token();$home=$this->homes->forUser($user);ob_start();require $this->views.'/dashboard.php';return new Response(200,(string)ob_get_clean());}
}
