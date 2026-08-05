<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class UserCategoryAccessSchema
{
    public const MIGRATION='202608010007_user_category_access';
    public const CATEGORIES=['operations','human-resources','company-notices','application-reviews','finance','marketing','admin','credit-cards'];
}
