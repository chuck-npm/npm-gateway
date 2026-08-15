<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class ApartmentsComSchema
{
 public const MIGRATION='202608150026_apartments_com_imports';
 public const TABLES=['apartments_imports','apartments_property_mappings','apartments_calls','apartments_email_leads'];
 public const MAPPINGS=['Boulder Trails'=>'BT','Crumley Farms'=>'CF','Flamingo Flats'=>'FF','Highridge'=>'HR','Maplewind MHC'=>'MW','Pearce Point'=>'PP','Pine Hill'=>'PH','Sizemore'=>'SM','Wunderpark'=>'WP'];
}
