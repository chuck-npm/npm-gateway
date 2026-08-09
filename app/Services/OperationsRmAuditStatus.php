<?php declare(strict_types=1);namespace NpmGateway\Services;
final class OperationsRmAuditStatus
{
 public const LABELS=['open'=>'Open','completed'=>'Completed'];
 public const BADGES=['open'=>'warning','completed'=>'success'];
 public static function project(string $authoritative):string{return$authoritative==='completed'?'completed':'open';}
}
