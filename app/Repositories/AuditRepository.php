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
    public function existsEventForEntitySince(string $eventType,string $entityPublicId,string $since):bool{$s=$this->connection->prepare('SELECT 1 FROM audit_logs USE INDEX (idx_audit_logs_entity_public) WHERE entity_public_id=? AND created_at>=? AND event_type=? LIMIT 1');$s->bind_param('sss',$entityPublicId,$since,$eventType);$s->execute();$exists=$s->get_result()->num_rows>0;$s->close();return $exists;}
}
