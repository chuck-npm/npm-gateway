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
        public ?string $routeName = null,
        public ?int $attentionCount = null,
        public ?string $attentionLabel = null,
        public ?string $attentionSemantic = null,
    ) {
        if (preg_match('/^[a-z][a-z0-9-]*$/', $key) !== 1) throw new InvalidArgumentException('Tool key must be stable and machine-safe.');
        foreach (['title'=>$title,'description'=>$description,'category'=>$categoryLabel,'footer'=>$footerLabel] as $field=>$value) {
            if (trim($value) === '') throw new InvalidArgumentException("Tool {$field} is required.");
        }
        if ($sortOrder < 0) throw new InvalidArgumentException('Tool sort order must be non-negative.');
        if ($enabled && ($route === null || preg_match('#^/[a-z0-9][a-z0-9/-]*$#', $route) !== 1)) throw new InvalidArgumentException('Enabled tools require an approved internal route.');
        if (!$enabled && $route !== null) throw new InvalidArgumentException('Disabled tools cannot expose a destination.');
        if ($enabled && ($routeName===null||preg_match('/^[a-z][a-z0-9-]*(?:\.[a-z][a-z0-9-]*)+$/',$routeName)!==1)) throw new InvalidArgumentException('Enabled tools require an approved internal route name.');
        if (!$enabled && $routeName!==null) throw new InvalidArgumentException('Disabled tools cannot expose a route name.');
        if($attentionCount!==null&&$attentionCount<1)throw new InvalidArgumentException('Attention count must be positive when present.');
        if($attentionCount!==null&&($attentionLabel===null||trim($attentionLabel)===''))throw new InvalidArgumentException('Attention label is required when a count is present.');
        if($attentionCount===null&&($attentionLabel!==null||$attentionSemantic!==null))throw new InvalidArgumentException('Attention metadata requires a positive count.');
        if($attentionSemantic!==null&&!in_array($attentionSemantic,['warning'],true))throw new InvalidArgumentException('Attention semantic is not approved.');
    }
}
