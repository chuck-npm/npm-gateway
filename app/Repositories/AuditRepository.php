<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\AuditStoreInterface;
final class AuditRepository implements AuditStoreInterface
{
    public function __construct(private readonly mysqli $connection) {}
    public function insert(array $event): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO audit_logs
             (public_id, user_id, employee_id, property_id, event_type, entity_type, entity_id,
              entity_public_id, description, after_data, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $afterData = json_encode($event['metadata'], JSON_THROW_ON_ERROR);
        $propertyId=$event['property_id']??null;
        $statement->bind_param(
            'siiississss',
            $event['public_id'], $event['user_id'], $event['employee_id'], $propertyId,$event['event_type'],
            $event['entity_type'], $event['entity_id'], $event['entity_public_id'],
            $event['description'], $afterData, $event['created_at']
        );
        $statement->execute();
        $statement->close();
    }
}
