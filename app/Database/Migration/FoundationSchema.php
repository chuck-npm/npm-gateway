<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

final class FoundationSchema
{
    public const MIGRATION = '202607270001_foundation';

    /** @return array<string, array{indexes: list<string>, foreign_keys: list<string>, checks: list<string>}> */
    public static function expectations(): array
    {
        return [
            'properties' => [
                'indexes' => ['PRIMARY', 'uq_properties_public_id', 'uq_properties_property_code', 'uq_properties_slug', 'uq_properties_manager_email', 'uq_properties_ivr_number'],
                'foreign_keys' => ['fk_properties_created_by', 'fk_properties_updated_by'],
                'checks' => ['chk_properties_code_format', 'chk_properties_slug_format', 'chk_properties_status', 'chk_properties_state_format', 'chk_properties_manager_email_lowercase'],
            ],
            'employees' => [
                'indexes' => ['PRIMARY', 'uq_employees_public_id', 'uq_employees_employee_number'],
                'foreign_keys' => ['fk_employees_supervisor', 'fk_employees_created_by', 'fk_employees_updated_by'],
                'checks' => ['chk_employees_number_format', 'chk_employees_class', 'chk_employees_status', 'chk_employees_business_email_lowercase', 'chk_employees_personal_email_lowercase', 'chk_employees_termination_dates'],
            ],
            'users' => [
                'indexes' => ['PRIMARY', 'uq_users_public_id', 'uq_users_employee_id', 'uq_users_username'],
                'foreign_keys' => ['fk_users_employee', 'fk_users_created_by', 'fk_users_updated_by'],
                'checks' => ['chk_users_username_lowercase', 'chk_users_username_format', 'chk_users_status', 'chk_users_must_change_password', 'chk_users_disabled_state'],
            ],
            'employee_property_assignments' => [
                'indexes' => ['PRIMARY', 'uq_employee_property_assignments_public_id', 'idx_assignments_employee_active', 'idx_assignments_property_active', 'idx_assignments_employee_primary'],
                'foreign_keys' => ['fk_assignments_employee', 'fk_assignments_property', 'fk_assignments_created_by', 'fk_assignments_updated_by'],
                'checks' => ['chk_assignments_type', 'chk_assignments_is_primary', 'chk_assignments_date_range'],
            ],
            'audit_logs' => [
                'indexes' => ['PRIMARY', 'uq_audit_logs_public_id', 'idx_audit_logs_entity', 'idx_audit_logs_entity_public'],
                'foreign_keys' => ['fk_audit_logs_user', 'fk_audit_logs_employee', 'fk_audit_logs_property'],
                'checks' => ['chk_audit_logs_ip_hash'],
            ],
        ];
    }
}
