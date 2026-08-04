<?php

declare(strict_types=1);

namespace NpmGateway\Database\Migration;

use mysqli;

final class SchemaVerifier
{
    public function __construct(
        private readonly mysqli $connection,
        private readonly MigrationRepository $repository,
        private readonly MigrationDiscovery $discovery,
        private readonly string $expectedDatabase
    ) {
    }

    /**
     * @return list<string>
     */
    public function verify(): array
    {
        $selectedDatabase = $this->scalar('SELECT DATABASE()');
        if ($selectedDatabase !== $this->expectedDatabase) {
            throw new MigrationException('The selected database does not match MIGRATION_DB_NAME.');
        }
        if (strcasecmp($this->connection->character_set_name(), 'utf8mb4') !== 0) {
            throw new MigrationException('The connection character set is not utf8mb4.');
        }

        $statement = $this->connection->prepare(
            'SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME
             FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?'
        );
        $database = $this->expectedDatabase;
        $statement->bind_param('s', $database);
        $statement->execute();
        $schema = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (
            !is_array($schema)
            || strcasecmp((string) $schema['DEFAULT_CHARACTER_SET_NAME'], 'utf8mb4') !== 0
        ) {
            throw new MigrationException('The database character set is invalid.');
        }

        self::validateTableDefinition($this->repository->tableDefinition($this->expectedDatabase));
        $records = $this->repository->all();
        foreach ($records as $record) {
            if ($record->migration === '' || $record->batch < 1) {
                throw new MigrationException('Migration history contains an invalid record.');
            }
        }

        $statuses = MigrationRunner::buildStatus($this->discovery->discover(), $records);
        $pending = 0;
        $orphaned = [];
        foreach ($statuses as $status) {
            if ($status->status === 'Pending') {
                $pending++;
            } elseif ($status->status === 'Missing file') {
                $orphaned[] = $status->migration;
            }
        }
        if ($orphaned !== []) {
            throw new MigrationException(
                'Orphaned migration records: ' . implode(', ', $orphaned)
            );
        }
        $executed = array_fill_keys(
            array_map(static fn (MigrationRecord $record): string => $record->migration, $records),
            true
        );
        $authenticationSecurityApplied = isset($executed[AuthenticationSecuritySchema::MIGRATION]);
        if (isset($executed[FoundationSchema::MIGRATION])) {
            $this->verifyFoundationSchema($authenticationSecurityApplied,isset($executed[EmployeeAdministrationSchema::MIGRATION]));
        }
        if ($authenticationSecurityApplied) {
            $this->verifyAuthenticationSecuritySchema();
        }
        if (isset($executed[PropertiesWorkspaceSchema::MIGRATION])) {
            $this->verifyPropertiesWorkspaceSchema();
        }
        if(isset($executed[CorporateContextSchema::MIGRATION])){$this->verifyCorporateContextSchema();}
        if(isset($executed[EmployeeAdministrationSchema::MIGRATION])){$this->verifyEmployeeAdministrationSchema();}
        if(isset($executed[EmployeeDateOfBirthSchema::MIGRATION])){$this->verifyEmployeeDateOfBirthSchema();}
        if(isset($executed[UserCategoryAccessSchema::MIGRATION])){$this->verifyUserCategoryAccessSchema();}
        if(isset($executed[NotificationsSchema::MIGRATION])){$this->verifyNotificationsSchema();}
        if(isset($executed[OperationsCategorySchema::MIGRATION])){$this->verifyOperationsCategorySchema();}elseif(isset($executed[CompanyNoticesCategorySchema::MIGRATION])){$this->verifyCompanyNoticesCategorySchema();}
        if(isset($executed[GatewayStorageSchema::MIGRATION])){$this->verifyGatewayStorageSchema();}
        if(isset($executed[StorageSystemCleanupActorSchema::MIGRATION])){$this->verifyStorageSystemCleanupActorSchema();}

        return [
            'Schema verification passed.',
            sprintf('Executed migrations: %d', count($records)),
            sprintf('Pending migrations: %d', $pending),
        ];
    }

    private function verifyPropertiesWorkspaceSchema(): void
    {
        $metadata=$this->tableMetadata('properties');
        foreach(PropertiesWorkspaceSchema::COLUMNS as $column){if(!isset($metadata['columns'][$column]))throw new MigrationException("Missing column {$column} on properties.");}
        foreach(PropertiesWorkspaceSchema::INDEXES as $index){if(!isset($metadata['indexes'][$index]))throw new MigrationException("Missing index {$index} on properties.");}
        foreach(PropertiesWorkspaceSchema::CHECKS as $check){if(!isset($metadata['checks'][$check]))throw new MigrationException("Missing check constraint {$check} on properties.");}
        $assignments=$this->tableMetadata('employee_property_assignments');
        foreach(PropertiesWorkspaceSchema::ASSIGNMENT_COLUMNS as $column){if(!isset($assignments['columns'][$column]))throw new MigrationException("Missing column {$column} on employee_property_assignments.");}
        foreach(PropertiesWorkspaceSchema::ASSIGNMENT_INDEXES as $index){if(!isset($assignments['indexes'][$index]))throw new MigrationException("Missing index {$index} on employee_property_assignments.");}
        $statement=$this->connection->prepare("SELECT COLUMN_NAME,GENERATION_EXPRESSION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='employee_property_assignments' AND COLUMN_NAME IN ('active_primary_manager_property_id','active_primary_manager_employee_id')");$database=$this->expectedDatabase;$statement->bind_param('s',$database);$statement->execute();$generated=[];foreach($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row){$expression=strtolower(str_replace(['`','(',')','_utf8mb4'], '',(string)$row['GENERATION_EXPRESSION']));$generated[(string)$row['COLUMN_NAME']]=preg_replace('/\s+/','',$expression)??'';}$statement->close();
        foreach(['active_primary_manager_property_id'=>'property_id','active_primary_manager_employee_id'=>'employee_id'] as $column=>$identity){$expression=$generated[$column]??'';foreach(['assignment_type','property_manager','is_primary=1','ends_onisnull',$identity,'elsenull'] as $required)if(!str_contains($expression,$required))throw new MigrationException("Invalid generated expression for {$column}.");if(str_contains($expression,'assistant_manager'))throw new MigrationException("Invalid generated expression for {$column}.");}
    }
    private function verifyCorporateContextSchema():void
    {
        $statement=$this->connection->prepare('SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=\'properties\' AND COLUMN_NAME=\'ivr_number\'');$database=$this->expectedDatabase;$statement->bind_param('s',$database);$statement->execute();$nullable=(string)($statement->get_result()->fetch_row()[0]??'');$statement->close();if($nullable!=='YES')throw new MigrationException('properties.ivr_number must be nullable after Corporate Context migration.');
    }
    private function verifyEmployeeAdministrationSchema():void
    {
        $statement=$this->connection->prepare("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='employees' AND COLUMN_NAME IN ('hire_date','start_date','comments')");
        $database=$this->expectedDatabase;$statement->bind_param('s',$database);$statement->execute();$result=$statement->get_result();$columns=[];while($row=$result->fetch_assoc())$columns[(string)$row['COLUMN_NAME']]=$row;$statement->close();
        if(isset($columns['hire_date']))throw new MigrationException('employees.hire_date must not exist after Employee Administration migration.');
        if(($columns['start_date']['COLUMN_TYPE']??'')!=='date'||($columns['start_date']['IS_NULLABLE']??'')!=='NO')throw new MigrationException('employees.start_date must be DATE NOT NULL.');
        if(($columns['comments']['COLUMN_TYPE']??'')!=='text'||($columns['comments']['IS_NULLABLE']??'')!=='YES')throw new MigrationException('employees.comments must be nullable TEXT.');
        $indexes=$this->tableMetadata('employees')['indexes'];if(!isset($indexes['idx_employees_start_date'])||isset($indexes['idx_employees_hire_date']))throw new MigrationException('Employee start-date index is invalid.');
    }
    private function verifyEmployeeDateOfBirthSchema():void
    {
        $statement=$this->connection->prepare("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,ORDINAL_POSITION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='employees' AND COLUMN_NAME IN ('date_of_birth','dob','birth_date','birthday','age','preferred_name')");
        $database=$this->expectedDatabase;$statement->bind_param('s',$database);$statement->execute();$result=$statement->get_result();$columns=[];while($row=$result->fetch_assoc())$columns[(string)$row['COLUMN_NAME']]=$row;$statement->close();
        if(($columns['date_of_birth']['COLUMN_TYPE']??'')!=='date'||($columns['date_of_birth']['IS_NULLABLE']??'')!=='YES')throw new MigrationException('employees.date_of_birth must be nullable DATE.');
        foreach(['dob','birth_date','birthday','age'] as $forbidden)if(isset($columns[$forbidden]))throw new MigrationException("employees.{$forbidden} is a forbidden duplicate or derived Date of Birth field.");
        if((int)($columns['date_of_birth']['ORDINAL_POSITION']??0)!==(int)($columns['preferred_name']['ORDINAL_POSITION']??-1)+1)throw new MigrationException('employees.date_of_birth must appear after preferred_name.');
        if(isset($this->tableMetadata('employees')['indexes']['idx_employees_date_of_birth']))throw new MigrationException('employees.date_of_birth must not have a general-purpose index.');
    }
    private function verifyUserCategoryAccessSchema():void
    {
        $metadata=$this->tableMetadata('user_category_access');foreach(['PRIMARY','uq_user_category_access_public_id','uq_user_category_access_user_category','idx_user_category_access_category','idx_user_category_access_granted_by','idx_user_category_access_updated_by'] as $index)if(!isset($metadata['indexes'][$index]))throw new MigrationException("Missing index {$index} on user_category_access.");foreach(['fk_user_category_access_user','fk_user_category_access_granted_by','fk_user_category_access_updated_by'] as $foreignKey)if(($metadata['foreign_keys'][$foreignKey]??'')!=='RESTRICT')throw new MigrationException("Invalid foreign key {$foreignKey} on user_category_access.");if(!isset($metadata['checks']['chk_user_category_access_category']))throw new MigrationException('Missing category check on user_category_access.');
    }
    private function verifyNotificationsSchema():void
    {
        $notice=$this->tableMetadata('notifications');$recipient=$this->tableMetadata('notification_recipients');
        foreach(NotificationsSchema::NOTIFICATION_INDEXES as $index)if(!isset($notice['indexes'][$index]))throw new MigrationException("Missing index {$index} on notifications.");
        foreach(NotificationsSchema::RECIPIENT_INDEXES as $index)if(!isset($recipient['indexes'][$index]))throw new MigrationException("Missing index {$index} on notification_recipients.");
        foreach(['fk_notifications_created_by'] as $key)if(($notice['foreign_keys'][$key]??'')!=='RESTRICT')throw new MigrationException("Invalid foreign key {$key}.");
        foreach(['fk_notification_recipients_notification','fk_notification_recipients_user'] as $key)if(($recipient['foreign_keys'][$key]??'')!=='RESTRICT')throw new MigrationException("Invalid foreign key {$key}.");
        foreach(['chk_notifications_type','chk_notifications_priority','chk_notifications_status','chk_notifications_ack'] as $check)if(!isset($notice['checks'][$check]))throw new MigrationException("Missing check {$check}.");
        if(!isset($recipient['checks']['chk_notification_recipients_email_status']))throw new MigrationException('Missing recipient email-status check.');
        $statement=$this->connection->prepare("SELECT DATA_TYPE,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='notifications' AND COLUMN_NAME='payload'");$database=$this->expectedDatabase;$statement->bind_param('s',$database);$statement->execute();$payload=$statement->get_result()->fetch_assoc();$statement->close();if(($payload['DATA_TYPE']??'')!=='json'||($payload['IS_NULLABLE']??'')!=='NO')throw new MigrationException('notifications.payload must be JSON NOT NULL.');
    }
    private function verifyCompanyNoticesCategorySchema():void
    {
        $this->assertCheckValues('user_category_access','chk_user_category_access_category',CompanyNoticesCategorySchema::CATEGORIES);$this->assertCheckValues('notifications','chk_notifications_type',CompanyNoticesCategorySchema::NOTIFICATION_TYPES);
    }
    private function verifyOperationsCategorySchema():void{$this->assertCheckValues('user_category_access','chk_user_category_access_category',OperationsCategorySchema::SQL_CATEGORIES);$this->assertCheckValues('notifications','chk_notifications_type',CompanyNoticesCategorySchema::NOTIFICATION_TYPES);}
    private function verifyGatewayStorageSchema():void
    {
        $storage=$this->tableMetadata('storage_objects');$links=$this->tableMetadata('notification_storage_objects');
        foreach(GatewayStorageSchema::STORAGE_INDEXES as $index)if(!isset($storage['indexes'][$index]))throw new MigrationException("Missing index {$index} on storage_objects.");
        foreach(GatewayStorageSchema::LINK_INDEXES as $index)if(!isset($links['indexes'][$index]))throw new MigrationException("Missing index {$index} on notification_storage_objects.");
        if(isset($links['indexes']['uq_notification_storage_objects_object']))throw new MigrationException('Storage objects must remain reusable across notifications.');
        foreach(GatewayStorageSchema::STORAGE_FOREIGN_KEYS as $key)if(($storage['foreign_keys'][$key]??'')!=='RESTRICT')throw new MigrationException("Invalid foreign key {$key} on storage_objects.");
        foreach(GatewayStorageSchema::LINK_FOREIGN_KEYS as $key)if(($links['foreign_keys'][$key]??'')!=='RESTRICT')throw new MigrationException("Invalid foreign key {$key} on notification_storage_objects.");
        foreach(GatewayStorageSchema::STORAGE_CHECKS as $check)if(!isset($storage['checks'][$check]))throw new MigrationException("Missing check {$check} on storage_objects.");
        if(!isset($links['checks']['chk_notification_storage_objects_role']))throw new MigrationException('Missing notification storage role check.');
    }
    private function verifyStorageSystemCleanupActorSchema():void
    {
        $statement=$this->connection->prepare("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='storage_objects' AND COLUMN_NAME='deleted_by_user_id'");$database=$this->expectedDatabase;$statement->bind_param('s',$database);$statement->execute();$nullable=$statement->get_result()->fetch_row()[0]??null;$statement->close();if($nullable!=='YES')throw new MigrationException('storage_objects.deleted_by_user_id must remain nullable.');
        $statement=$this->connection->prepare("SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=? AND tc.TABLE_NAME='storage_objects' AND tc.CONSTRAINT_NAME='chk_storage_objects_lifecycle_metadata'");$statement->bind_param('s',$database);$statement->execute();$clause=$statement->get_result()->fetch_row()[0]??null;$statement->close();if(!is_string($clause)||StorageSystemCleanupActorSchema::normalize($clause)!==StorageSystemCleanupActorSchema::normalize(StorageSystemCleanupActorSchema::AFTER))throw new MigrationException('Invalid Storage System Cleanup Actor lifecycle constraint.');
    }
    private function assertCheckValues(string $table,string $constraint,array $expected):void{$statement=$this->connection->prepare("SELECT cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=? AND tc.TABLE_NAME=? AND tc.CONSTRAINT_NAME=? AND tc.CONSTRAINT_TYPE='CHECK'");$database=$this->expectedDatabase;$statement->bind_param('sss',$database,$table,$constraint);$statement->execute();$clause=$statement->get_result()->fetch_row()[0]??null;$statement->close();if(!is_string($clause))throw new MigrationException("Missing check constraint {$constraint}.");preg_match_all("/'([^']+)'/",str_replace("\\'","'",$clause),$matches);if(array_values(array_unique($matches[1]??[]))!==$expected)throw new MigrationException("Invalid permitted values for {$constraint}.");}

    private function verifyFoundationSchema(bool $authenticationSecurityApplied,bool $employeeAdministrationApplied): void
    {
        foreach (FoundationSchema::expectations() as $table => $expected) {
            $metadata = $this->tableMetadata($table);
            if (strcasecmp($metadata['engine'], 'InnoDB') !== 0) {
                throw new MigrationException("{$table} must use InnoDB.");
            }
            if (strcasecmp($metadata['collation'], 'utf8mb4_0900_ai_ci') !== 0) {
                throw new MigrationException("{$table} has an invalid collation.");
            }
            foreach ($expected['indexes'] as $index) {
                if($employeeAdministrationApplied&&$table==='employees'&&$index==='idx_employees_hire_date')continue;
                if (!isset($metadata['indexes'][$index])) {
                    throw new MigrationException("Missing index {$index} on {$table}.");
                }
            }
            foreach ($expected['foreign_keys'] as $foreignKey) {
                if (!isset($metadata['foreign_keys'][$foreignKey])) {
                    throw new MigrationException("Missing foreign key {$foreignKey} on {$table}.");
                }
                if ($metadata['foreign_keys'][$foreignKey] === 'CASCADE') {
                    throw new MigrationException("Unexpected ON DELETE CASCADE on {$foreignKey}.");
                }
            }
            foreach ($expected['checks'] as $check) {
                if ($authenticationSecurityApplied && $check === 'chk_users_must_change_password') {
                    continue;
                }
                if (!isset($metadata['checks'][$check])) {
                    throw new MigrationException("Missing check constraint {$check} on {$table}.");
                }
            }
        }

        $userColumns = $this->tableMetadata('users')['columns'];
        if ($authenticationSecurityApplied && isset($userColumns['must_change_password'])) {
            throw new MigrationException('users must not contain must_change_password after Authentication Security.');
        }

        $auditColumns = $this->tableMetadata('audit_logs')['columns'];
        foreach (['updated_at', 'created_by', 'updated_by'] as $forbidden) {
            if (isset($auditColumns[$forbidden])) {
                throw new MigrationException("audit_logs must not contain {$forbidden}.");
            }
        }
    }

    private function verifyAuthenticationSecuritySchema(): void
    {
        foreach (AuthenticationSecuritySchema::expectations() as $table => $expected) {
            $metadata = $this->tableMetadata($table);
            if (strcasecmp($metadata['engine'], 'InnoDB') !== 0) {
                throw new MigrationException("{$table} must use InnoDB.");
            }
            if (strcasecmp($metadata['collation'], 'utf8mb4_0900_ai_ci') !== 0) {
                throw new MigrationException("{$table} has an invalid collation.");
            }
            foreach ($expected['columns'] as $column) {
                if (!isset($metadata['columns'][$column])) {
                    throw new MigrationException("Missing column {$column} on {$table}.");
                }
            }
            foreach ($expected['forbidden_columns'] as $column) {
                if (isset($metadata['columns'][$column])) {
                    throw new MigrationException("Forbidden column {$column} exists on {$table}.");
                }
            }
            foreach ($expected['indexes'] as $index) {
                if (!isset($metadata['indexes'][$index])) {
                    throw new MigrationException("Missing index {$index} on {$table}.");
                }
            }
            foreach ($expected['foreign_keys'] as $foreignKey) {
                if (!isset($metadata['foreign_keys'][$foreignKey])) {
                    throw new MigrationException("Missing foreign key {$foreignKey} on {$table}.");
                }
                if ($metadata['foreign_keys'][$foreignKey] === 'CASCADE') {
                    throw new MigrationException("Unexpected ON DELETE CASCADE on {$foreignKey}.");
                }
            }
            foreach ($expected['checks'] as $check) {
                if (!isset($metadata['checks'][$check])) {
                    throw new MigrationException("Missing check constraint {$check} on {$table}.");
                }
            }
        }
    }

    /**
     * @return array{
     *   engine: string,
     *   collation: string,
     *   columns: array<string, true>,
     *   indexes: array<string, true>,
     *   foreign_keys: array<string, string>,
     *   checks: array<string, true>
     * }
     */
    private function tableMetadata(string $table): array
    {
        $statement = $this->connection->prepare(
            'SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $database = $this->expectedDatabase;
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $row = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!is_array($row)) {
            throw new MigrationException("Missing expected table: {$table}.");
        }

        $columns = $this->namedSet(
            'SELECT COLUMN_NAME AS item_name FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            $table
        );
        $indexes = $this->namedSet(
            'SELECT DISTINCT INDEX_NAME AS item_name FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            $table
        );
        $checks = $this->namedSet(
            "SELECT CONSTRAINT_NAME AS item_name FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'CHECK'",
            $table
        );

        $statement = $this->connection->prepare(
            'SELECT CONSTRAINT_NAME, DELETE_RULE
             FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ?'
        );
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $result = $statement->get_result();
        $foreignKeys = [];
        while ($foreignKey = $result->fetch_assoc()) {
            $foreignKeys[(string) $foreignKey['CONSTRAINT_NAME']] = strtoupper((string) $foreignKey['DELETE_RULE']);
        }
        $statement->close();

        return [
            'engine' => (string) $row['ENGINE'],
            'collation' => (string) $row['TABLE_COLLATION'],
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
            'checks' => $checks,
        ];
    }

    /** @return array<string, true> */
    private function namedSet(string $sql, string $table): array
    {
        $statement = $this->connection->prepare($sql);
        $database = $this->expectedDatabase;
        $statement->bind_param('ss', $database, $table);
        $statement->execute();
        $result = $statement->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[(string) $row['item_name']] = true;
        }
        $statement->close();

        return $items;
    }

    /**
     * @param array{engine: string, collation: string, columns: list<array<string, string>>, indexes: list<array<string, string>>} $definition
     */
    public static function validateTableDefinition(array $definition): void
    {
        if (strcasecmp($definition['engine'], 'InnoDB') !== 0) {
            throw new MigrationException('The migrations table engine must be InnoDB.');
        }
        if (strcasecmp($definition['collation'], 'utf8mb4_0900_ai_ci') !== 0) {
            throw new MigrationException('The migrations table collation is invalid.');
        }

        $expected = [
            'id' => ['bigint unsigned', 'NO', null, 'auto_increment'],
            'migration' => ['varchar(255)', 'NO', null, ''],
            'batch' => ['int unsigned', 'NO', null, ''],
            'executed_at' => ['datetime', 'NO', 'CURRENT_TIMESTAMP', 'DEFAULT_GENERATED'],
        ];
        if (count($definition['columns']) !== count($expected)) {
            throw new MigrationException('The migrations table has an unexpected column set.');
        }
        foreach ($definition['columns'] as $column) {
            $name = strtolower($column['COLUMN_NAME'] ?? '');
            if (!isset($expected[$name])) {
                throw new MigrationException("Unexpected migrations column: {$name}");
            }
            [$type, $nullable, $default, $extra] = $expected[$name];
            $actualExtra = strtoupper($column['EXTRA'] ?? '');
            if (
                strtolower($column['COLUMN_TYPE'] ?? '') !== $type
                || strtoupper($column['IS_NULLABLE'] ?? '') !== $nullable
                || ($column['COLUMN_DEFAULT'] ?? null) !== $default
                || ($extra !== '' && !str_contains($actualExtra, strtoupper($extra)))
                || ($extra === '' && $actualExtra !== '')
            ) {
                throw new MigrationException("Invalid migrations column definition: {$name}");
            }
        }

        $indexes = [];
        foreach ($definition['indexes'] as $index) {
            $name = (string) ($index['INDEX_NAME'] ?? '');
            $indexes[$name][] = [
                'column' => strtolower((string) ($index['COLUMN_NAME'] ?? '')),
                'non_unique' => (int) ($index['NON_UNIQUE'] ?? 1),
            ];
        }
        if (($indexes['PRIMARY'][0] ?? null) !== ['column' => 'id', 'non_unique' => 0]) {
            throw new MigrationException('The migrations primary key is invalid.');
        }
        if (
            ($indexes['uq_migrations_migration'][0] ?? null)
            !== ['column' => 'migration', 'non_unique' => 0]
        ) {
            throw new MigrationException('The migrations unique index is missing or invalid.');
        }
        if (
            ($indexes['idx_migrations_batch'][0] ?? null)
            !== ['column' => 'batch', 'non_unique' => 1]
        ) {
            throw new MigrationException('The migrations batch index is missing or invalid.');
        }
    }

    private function scalar(string $sql): string
    {
        $result = $this->connection->query($sql);
        $row = $result->fetch_row();
        $result->free();

        return (string) ($row[0] ?? '');
    }
}
