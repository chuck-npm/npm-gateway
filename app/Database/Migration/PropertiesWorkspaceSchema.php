<?php
declare(strict_types=1);
namespace NpmGateway\Database\Migration;
final class PropertiesWorkspaceSchema
{
    public const MIGRATION='202607310003_properties_workspace';
    public const COLUMNS=['prop_id','office_phone','ivr_routing_email'];
    public const INDEXES=['uq_properties_prop_id'];
    public const CHECKS=['chk_properties_prop_id_positive','chk_properties_ivr_routing_email_lowercase'];
    public const ASSIGNMENT_COLUMNS=['active_primary_manager_property_id','active_primary_manager_employee_id'];
    public const ASSIGNMENT_INDEXES=['uq_assignments_active_primary_manager_property','uq_assignments_active_primary_manager_employee'];
}
