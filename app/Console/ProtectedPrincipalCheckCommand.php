<?php
declare(strict_types=1);
namespace NpmGateway\Console;
use NpmGateway\Services\ProtectedPrincipalService;
final class ProtectedPrincipalCheckCommand
{
    public function __construct(private readonly ProtectedPrincipalService $service){}
    public function run(array $arguments):array
    {
        if($arguments!==[])return ['exit_code'=>2,'stdout'=>'','stderr'=>"This command does not accept arguments.\n"];
        $health=$this->service->health();if($health['healthy'])return ['exit_code'=>0,'stdout'=>"Protected principal: healthy.\n",'stderr'=>''];return ['exit_code'=>1,'stdout'=>'','stderr'=>'Protected principal: unhealthy ('.implode(', ',$health['reasons']).").\n"];
    }
}
