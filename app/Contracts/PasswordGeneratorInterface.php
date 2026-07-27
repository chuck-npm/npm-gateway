<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface PasswordGeneratorInterface { public function generate(): string; }
