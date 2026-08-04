<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class CompanyNoticesCategorySchema
{
 public const MIGRATION='202608020009_company_notices_category';
 public const BEFORE_CATEGORIES=['finance','human-resources','marketing','admin','credit-cards'];
 public const CATEGORIES=['finance','human-resources','company-notices','marketing','admin','credit-cards'];
 public const BEFORE_TYPES=['employee_created'];
 public const NOTIFICATION_TYPES=['employee_created','company_notice'];
}
