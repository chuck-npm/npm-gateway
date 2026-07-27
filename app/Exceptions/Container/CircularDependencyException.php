<?php
declare(strict_types=1);
namespace NpmGateway\Exceptions\Container;
use RuntimeException;
final class CircularDependencyException extends RuntimeException {}
