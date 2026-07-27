<?php

declare(strict_types=1);

use NpmGateway\Database\Migration\MigrationInterface;

return new class implements MigrationInterface
{
    public function up(mysqli $connection): void
    {
        $connection->query(<<<'SQL'
CREATE TABLE properties (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COMMENT 'Stable ULID-style public identifier.',
    property_code CHAR(2) NOT NULL COMMENT 'Immutable two-character internal NPM property code such as HR or PP.',
    slug VARCHAR(64) NOT NULL COMMENT 'Immutable lowercase application and URL identifier such as highridge.',
    display_name VARCHAR(150) NOT NULL COMMENT 'Current human-readable property name.',
    legal_name VARCHAR(200) NULL COMMENT 'Legal entity or ownership name when different from the display name.',
    status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Operational status: active, inactive, sold, or archived.',
    manager_email VARCHAR(254) NOT NULL COMMENT 'Reusable operational manager mailbox belonging to the property.',
    ivr_number VARCHAR(20) NOT NULL COMMENT 'Permanent advertising and IVR telephone number in normalized format.',
    website_url VARCHAR(255) NULL,
    address_line_1 VARCHAR(150) NOT NULL,
    address_line_2 VARCHAR(150) NULL,
    city VARCHAR(100) NOT NULL,
    state CHAR(2) NOT NULL COMMENT 'Uppercase US postal abbreviation.',
    postal_code VARCHAR(10) NOT NULL,
    timezone VARCHAR(64) NOT NULL COMMENT 'IANA timezone such as America/New_York.',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_properties_public_id (public_id),
    UNIQUE KEY uq_properties_property_code (property_code),
    UNIQUE KEY uq_properties_slug (slug),
    UNIQUE KEY uq_properties_manager_email (manager_email),
    UNIQUE KEY uq_properties_ivr_number (ivr_number),
    KEY idx_properties_status (status),
    KEY idx_properties_state_city (state, city),
    CONSTRAINT chk_properties_code_format CHECK (property_code REGEXP '^[A-Z]{2}$'),
    CONSTRAINT chk_properties_slug_format CHECK (slug REGEXP '^[a-z][a-z0-9-]*$'),
    CONSTRAINT chk_properties_status CHECK (status IN ('active', 'inactive', 'sold', 'archived')),
    CONSTRAINT chk_properties_state_format CHECK (state REGEXP '^[A-Z]{2}$'),
    CONSTRAINT chk_properties_manager_email_lowercase CHECK (manager_email = LOWER(manager_email))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Permanent NPM property identity and operational contact information.'
SQL);

        $connection->query(<<<'SQL'
CREATE TABLE employees (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COMMENT 'Stable ULID-style public identifier.',
    employee_number VARCHAR(12) NOT NULL COMMENT 'Permanent Gateway-generated employee number such as NPM000017.',
    employee_class VARCHAR(20) NOT NULL COMMENT 'Corporate, manager, or maintenance.',
    first_name VARCHAR(75) NOT NULL,
    middle_name VARCHAR(75) NULL,
    last_name VARCHAR(75) NOT NULL,
    preferred_name VARCHAR(75) NULL,
    business_email VARCHAR(254) NULL COMMENT 'Reachable business email when assigned directly to the employee.',
    personal_email VARCHAR(254) NULL,
    company_phone VARCHAR(30) NULL COMMENT 'Company-issued phone assigned to corporate staff, managers, or assistant managers.',
    personal_phone VARCHAR(30) NULL,
    job_title VARCHAR(100) NOT NULL,
    employment_status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Active, leave, inactive, or terminated.',
    hire_date DATE NOT NULL,
    termination_date DATE NULL,
    supervisor_employee_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_employees_public_id (public_id),
    UNIQUE KEY uq_employees_employee_number (employee_number),
    UNIQUE KEY uq_employees_business_email (business_email),
    KEY idx_employees_name (last_name, first_name),
    KEY idx_employees_class_status (employee_class, employment_status),
    KEY idx_employees_supervisor (supervisor_employee_id),
    KEY idx_employees_hire_date (hire_date),
    CONSTRAINT fk_employees_supervisor FOREIGN KEY (supervisor_employee_id) REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_employees_number_format CHECK (employee_number REGEXP '^NPM[0-9]{6}$'),
    CONSTRAINT chk_employees_class CHECK (employee_class IN ('corporate', 'manager', 'maintenance')),
    CONSTRAINT chk_employees_status CHECK (employment_status IN ('active', 'leave', 'inactive', 'terminated')),
    CONSTRAINT chk_employees_business_email_lowercase CHECK (business_email IS NULL OR business_email = LOWER(business_email)),
    CONSTRAINT chk_employees_personal_email_lowercase CHECK (personal_email IS NULL OR personal_email = LOWER(personal_email)),
    CONSTRAINT chk_employees_termination_dates CHECK (termination_date IS NULL OR termination_date >= hire_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Employee history is retained; employees are disabled or terminated rather than deleted.'
SQL);

        $connection->query(<<<'SQL'
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL COMMENT 'Stable ULID-style public identifier.',
    employee_id BIGINT UNSIGNED NOT NULL,
    username VARCHAR(50) NOT NULL COMMENT 'Permanent simple Gateway login name such as chuck, pat, or john2.',
    password_hash VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'Pending, active, locked, or disabled.',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    password_changed_at DATETIME NULL,
    password_reset_at DATETIME NULL COMMENT 'Most recent administrator-performed password reset.',
    last_login_at DATETIME NULL,
    failed_login_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    disabled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_public_id (public_id),
    UNIQUE KEY uq_users_employee_id (employee_id),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_status (status),
    KEY idx_users_locked_until (locked_until),
    CONSTRAINT fk_users_employee FOREIGN KEY (employee_id) REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_users_username_lowercase CHECK (username = LOWER(username)),
    CONSTRAINT chk_users_username_format CHECK (username REGEXP '^[a-z][a-z0-9]{1,49}$'),
    CONSTRAINT chk_users_status CHECK (status IN ('pending', 'active', 'locked', 'disabled')),
    CONSTRAINT chk_users_must_change_password CHECK (must_change_password IN (0, 1)),
    CONSTRAINT chk_users_disabled_state CHECK (disabled_at IS NULL OR status = 'disabled')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Permanent person-specific Gateway authentication identities; usernames are never reassigned.'
SQL);

        $connection->query(
            'ALTER TABLE users
             ADD CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
             ADD CONSTRAINT fk_users_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT'
        );
        $connection->query(
            'ALTER TABLE properties
             ADD CONSTRAINT fk_properties_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
             ADD CONSTRAINT fk_properties_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT'
        );
        $connection->query(
            'ALTER TABLE employees
             ADD CONSTRAINT fk_employees_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
             ADD CONSTRAINT fk_employees_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT'
        );

        $connection->query(<<<'SQL'
CREATE TABLE employee_property_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    assignment_type VARCHAR(30) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    starts_on DATE NOT NULL,
    ends_on DATE NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_employee_property_assignments_public_id (public_id),
    KEY idx_assignments_employee_active (employee_id, ends_on),
    KEY idx_assignments_property_active (property_id, ends_on),
    KEY idx_assignments_employee_primary (employee_id, is_primary, ends_on),
    KEY idx_assignments_property_type (property_id, assignment_type, ends_on),
    KEY idx_assignments_date_range (starts_on, ends_on),
    CONSTRAINT fk_assignments_employee FOREIGN KEY (employee_id) REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_property FOREIGN KEY (property_id) REFERENCES properties (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_assignments_type CHECK (assignment_type IN ('property_manager', 'assistant_manager', 'floating_manager', 'maintenance', 'temporary_coverage', 'regional_support')),
    CONSTRAINT chk_assignments_is_primary CHECK (is_primary IN (0, 1)),
    CONSTRAINT chk_assignments_date_range CHECK (ends_on IS NULL OR ends_on >= starts_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Current and historical assignments; active assignments have a null ends_on.'
SQL);

        $connection->query(<<<'SQL'
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    public_id CHAR(26) NOT NULL,
    user_id BIGINT UNSIGNED NULL COMMENT 'Gateway account responsible for the event, null for system actions.',
    employee_id BIGINT UNSIGNED NULL COMMENT 'Employee identity responsible for the event when known.',
    property_id BIGINT UNSIGNED NULL COMMENT 'Related property when the event is property-specific.',
    event_type VARCHAR(100) NOT NULL COMMENT 'Stable machine-readable event name such as employee.updated.',
    entity_type VARCHAR(100) NULL,
    entity_id BIGINT UNSIGNED NULL,
    entity_public_id CHAR(26) NULL,
    description TEXT NOT NULL,
    before_data JSON NULL,
    after_data JSON NULL,
    ip_hash CHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_audit_logs_public_id (public_id),
    KEY idx_audit_logs_created_at (created_at),
    KEY idx_audit_logs_user_created (user_id, created_at),
    KEY idx_audit_logs_employee_created (employee_id, created_at),
    KEY idx_audit_logs_property_created (property_id, created_at),
    KEY idx_audit_logs_event_created (event_type, created_at),
    KEY idx_audit_logs_entity (entity_type, entity_id, created_at),
    KEY idx_audit_logs_entity_public (entity_public_id, created_at),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_audit_logs_employee FOREIGN KEY (employee_id) REFERENCES employees (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT fk_audit_logs_property FOREIGN KEY (property_id) REFERENCES properties (id) ON UPDATE RESTRICT ON DELETE RESTRICT,
    CONSTRAINT chk_audit_logs_ip_hash CHECK (ip_hash IS NULL OR ip_hash REGEXP '^[0-9a-f]{64}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
COMMENT='Append-only operational and security history; sensitive values must be redacted before insertion.'
SQL);
    }

    public function down(mysqli $connection): void
    {
        $connection->query('DROP TABLE IF EXISTS audit_logs');
        $connection->query('DROP TABLE IF EXISTS employee_property_assignments');
        $connection->query('ALTER TABLE properties DROP FOREIGN KEY fk_properties_created_by');
        $connection->query('ALTER TABLE properties DROP FOREIGN KEY fk_properties_updated_by');
        $connection->query('ALTER TABLE employees DROP FOREIGN KEY fk_employees_created_by');
        $connection->query('ALTER TABLE employees DROP FOREIGN KEY fk_employees_updated_by');
        $connection->query('DROP TABLE IF EXISTS users');
        $connection->query('DROP TABLE IF EXISTS employees');
        $connection->query('DROP TABLE IF EXISTS properties');
    }
};
