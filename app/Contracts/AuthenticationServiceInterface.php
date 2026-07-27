<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\AuthenticationResult;
use NpmGateway\ValueObjects\ClientContext;
use NpmGateway\ValueObjects\LoginRequest;
interface AuthenticationServiceInterface { public function authenticate(LoginRequest $request,ClientContext $context):AuthenticationResult; }
