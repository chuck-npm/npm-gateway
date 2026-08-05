<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class ApplicationReviewsCategorySchema
{
 public const MIGRATION='202608050016_application_reviews_category';
 public const BEFORE_CATEGORIES=['operations','finance','human-resources','company-notices','marketing','admin','credit-cards'];
 public const SQL_CATEGORIES=['operations','finance','human-resources','company-notices','application-reviews','marketing','admin','credit-cards'];
 public const CATEGORIES=['operations','human-resources','company-notices','application-reviews','finance','marketing','admin','credit-cards'];
}
