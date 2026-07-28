<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
use InvalidArgumentException;
final readonly class ToolCard
{
    public function __construct(
        public string $key,
        public string $title,
        public string $description,
        public string $categoryLabel,
        public string $footerLabel,
        public ?string $route,
        public bool $enabled,
        public int $sortOrder,
        public ?string $badgeLabel = null,
        public ?string $accessibilityLabel = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9-]*$/', $key) !== 1) throw new InvalidArgumentException('Tool key must be stable and machine-safe.');
        foreach (['title'=>$title,'description'=>$description,'category'=>$categoryLabel,'footer'=>$footerLabel] as $field=>$value) {
            if (trim($value) === '') throw new InvalidArgumentException("Tool {$field} is required.");
        }
        if ($sortOrder < 0) throw new InvalidArgumentException('Tool sort order must be non-negative.');
        if ($enabled && ($route === null || preg_match('#^/[a-z0-9][a-z0-9/-]*$#', $route) !== 1)) throw new InvalidArgumentException('Enabled tools require an approved internal route.');
        if (!$enabled && $route !== null) throw new InvalidArgumentException('Disabled tools cannot expose a destination.');
    }
}
