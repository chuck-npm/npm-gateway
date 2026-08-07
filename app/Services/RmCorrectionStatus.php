<?php
declare(strict_types=1);
namespace NpmGateway\Services;
final class RmCorrectionStatus
{
 public const LABELS=['pending_review'=>'Pending Review','approved'=>'Approved','denied'=>'Denied','more_information_needed'=>'More Information Needed'];
 public const BADGES=['pending_review'=>'neutral','approved'=>'success','denied'=>'danger','more_information_needed'=>'warning'];
 public static function valid(string $status):bool{return isset(self::LABELS[$status]);}
}
