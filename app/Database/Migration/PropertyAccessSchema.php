<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class PropertyAccessSchema
{
 public const MIGRATION='202608040014_user_property_access';
 public const TABLE='user_property_access';
 public const COLUMNS=['id','public_id','user_id','property_id','granted_by_user_id','granted_at','updated_by_user_id','updated_at'];
 public const INDEXES=['PRIMARY','uq_user_property_access_public_id','uq_user_property_access_user_property','idx_user_property_access_property','idx_user_property_access_granted_by','idx_user_property_access_updated_by'];
 public const FOREIGN_KEYS=['fk_user_property_access_user','fk_user_property_access_property','fk_user_property_access_granted_by','fk_user_property_access_updated_by'];
}
