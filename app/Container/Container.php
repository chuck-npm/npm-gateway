<?php
declare(strict_types=1);
namespace NpmGateway\Container;
use Closure;
use NpmGateway\Exceptions\Container\CircularDependencyException;
use NpmGateway\Exceptions\Container\ServiceNotFoundException;
final class Container
{
    /** @var array<string, Closure(self): mixed> */
    private array $definitions = [];
    /** @var array<string, mixed> */
    private array $instances = [];
    /** @var array<string, true> */
    private array $resolving = [];
    public function set(string $id, Closure $factory): void { $this->definitions[$id] = $factory; }
    public function instance(string $id, mixed $service): void { $this->instances[$id] = $service; }
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) { return $this->instances[$id]; }
        if (!isset($this->definitions[$id])) { throw new ServiceNotFoundException("Service is not registered: {$id}"); }
        if (isset($this->resolving[$id])) { throw new CircularDependencyException("Circular service dependency: {$id}"); }
        $this->resolving[$id] = true;
        try { return $this->instances[$id] = ($this->definitions[$id])($this); }
        finally { unset($this->resolving[$id]); }
    }
}
