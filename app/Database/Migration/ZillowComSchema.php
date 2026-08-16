<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class ZillowComSchema
{
 public const MIGRATION='202608160027_zillow_com_imports';
 public const TABLES=['zillow_imports','zillow_property_mappings','zillow_leads'];
 public const MAPPINGS=['Boulder Trails'=>'BT','Crumley Farms'=>'CF','Flamingo Flats'=>'FF','Highridge'=>'HR','Maplewind MHP'=>'MW','Pearce Pointe'=>'PP','Pine Hill MHC'=>'PH','Wunderpark'=>'WP'];
}
