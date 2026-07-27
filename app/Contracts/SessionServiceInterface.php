<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\ClientContext;
use NpmGateway\ValueObjects\SessionValidationResult;
interface SessionServiceInterface { public function validate(string $raw,ClientContext $context):SessionValidationResult; public function logout(string $raw,ClientContext $context):void; }
