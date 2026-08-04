<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class OperationsCategorySchema
{
 public const MIGRATION='202608030012_operations_category';
 public const BEFORE_CATEGORIES=['finance','human-resources','company-notices','marketing','admin','credit-cards'];
 public const CATEGORIES=['operations','human-resources','company-notices','finance','marketing','admin','credit-cards'];
 public const SQL_CATEGORIES=['operations','finance','human-resources','company-notices','marketing','admin','credit-cards'];
}
