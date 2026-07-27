<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
interface SessionTokenGeneratorInterface { public function generate(): string; }
