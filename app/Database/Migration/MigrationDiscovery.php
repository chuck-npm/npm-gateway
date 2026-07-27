<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

final class MigrationDiscovery
{
    /** @var array<string, array{name: string, filename: string, path: string}>|null */
    private ?array $discovered = null;

    /** @var array<string, MigrationInterface> */
    private array $loaded = [];

    public function __construct(private readonly string $directory)
    {
    }

    /**
     * @return list<array{name: string, filename: string, path: string}>
     */
    public function discover(): array
    {
        if ($this->discovered !== null) {
            return array_values($this->discovered);
        }
        if (!is_dir($this->directory) || !is_readable($this->directory)) {
            throw new MigrationException('The migration directory is missing or unreadable.');
        }

        $entries = scandir($this->directory);
        if ($entries === false) {
            throw new MigrationException('Unable to scan the migration directory.');
        }

        $found = [];
        foreach ($entries as $filename) {
            if (self::shouldIgnore($filename)) {
                continue;
            }
            if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'php') {
                continue;
            }
            if (!preg_match('/^(\d{12})_([a-z][a-z0-9]*(?:_[a-z0-9]+)*)\.php$/', $filename)) {
                throw new MigrationException("Malformed migration filename: {$filename}");
            }

            $name = substr($filename, 0, -4);
            if (isset($found[$name])) {
                throw new MigrationException("Duplicate migration name: {$name}");
            }
            $found[$name] = [
                'name' => $name,
                'filename' => $filename,
                'path' => $this->directory . DIRECTORY_SEPARATOR . $filename,
            ];
        }

        ksort($found, SORT_STRING);
        $this->discovered = $found;

        return array_values($found);
    }

    public function load(string $name): MigrationInterface
    {
        if (isset($this->loaded[$name])) {
            return $this->loaded[$name];
        }

        $metadata = null;
        foreach ($this->discover() as $candidate) {
            if ($candidate['name'] === $name) {
                $metadata = $candidate;
                break;
            }
        }
        if ($metadata === null) {
            throw new MigrationException("Migration file is missing: {$name}");
        }

        $migration = require $metadata['path'];
        if (!$migration instanceof MigrationInterface) {
            throw new MigrationException("Migration must return MigrationInterface: {$metadata['filename']}");
        }

        return $this->loaded[$name] = $migration;
    }

    public static function isValidFilename(string $filename): bool
    {
        return preg_match('/^\d{12}_[a-z][a-z0-9]*(?:_[a-z0-9]+)*\.php$/', $filename) === 1;
    }

    private static function shouldIgnore(string $filename): bool
    {
        return $filename === ''
            || $filename[0] === '.'
            || preg_match('/^(README(?:\..*)?|.*(?:~|\.bak|\.backup|\.swp|\.tmp))$/i', $filename) === 1;
    }
}
