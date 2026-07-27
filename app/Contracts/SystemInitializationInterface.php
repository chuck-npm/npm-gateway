<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\InitializeAdministratorRequest;
use NpmGateway\ValueObjects\InitializeAdministratorResult;
interface SystemInitializationInterface
{
    public function initialize(InitializeAdministratorRequest $request): InitializeAdministratorResult;
}
