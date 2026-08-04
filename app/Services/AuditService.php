<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\AuditStoreInterface;
use NpmGateway\Support\PublicIdGenerator;
final class AuditService
{
    private const FORBIDDEN = ['password', 'password_hash', 'session_token', 'session_token_hash', 'smtp_password', 'database_password', 'app_key'];
    public function __construct(
        private readonly AuditStoreInterface $audits,
        private readonly PublicIdGenerator $publicIds
    ) {}
    /** @param array<string, mixed> $metadata */
    public function record(string $eventType, int $userId, int $employeeId, string $userPublicId, string $description, array $metadata, string $createdAt): void
    {
        $this->assertSafe($metadata);
        $this->audits->insert([
            'public_id' => $this->publicIds->generate(), 'user_id' => $userId, 'employee_id' => $employeeId,
            'event_type' => $eventType, 'entity_type' => 'user', 'entity_id' => $userId,
            'entity_public_id' => $userPublicId, 'description' => $description,
            'metadata' => $metadata, 'created_at' => $createdAt,
        ]);
    }
    public function recordProperty(string $eventType,int $userId,int $employeeId,int $propertyId,string $propertyPublicId,string $description,array $metadata,string $createdAt):void
    {
        $this->assertSafe($metadata);$this->audits->insert(['public_id'=>$this->publicIds->generate(),'user_id'=>$userId,'employee_id'=>$employeeId,'property_id'=>$propertyId,'event_type'=>$eventType,'entity_type'=>'property','entity_id'=>$propertyId,'entity_public_id'=>$propertyPublicId,'description'=>$description,'metadata'=>$metadata,'created_at'=>$createdAt]);
    }
    public function recordSystemProperty(string $eventType,int $propertyId,string $propertyPublicId,string $description,array $metadata,string $createdAt):void
    {
        $this->assertSafe($metadata);$this->audits->insert(['public_id'=>$this->publicIds->generate(),'user_id'=>null,'employee_id'=>null,'property_id'=>$propertyId,'event_type'=>$eventType,'entity_type'=>'property','entity_id'=>$propertyId,'entity_public_id'=>$propertyPublicId,'description'=>$description,'metadata'=>$metadata,'created_at'=>$createdAt]);
    }
    public function recordSystem(string $eventType,string $entityType,int $entityId,string $entityPublicId,string $description,array $metadata,string $createdAt):void{$this->assertSafe($metadata);$this->audits->insert(['public_id'=>$this->publicIds->generate(),'user_id'=>null,'employee_id'=>null,'event_type'=>$eventType,'entity_type'=>$entityType,'entity_id'=>$entityId,'entity_public_id'=>$entityPublicId,'description'=>$description,'metadata'=>$metadata,'created_at'=>$createdAt]);}
    /** @param array<string, mixed> $metadata */
    private function assertSafe(array $metadata): void
    {
        foreach ($metadata as $key => $value) {
            $normalized = strtolower((string) $key);
            foreach (self::FORBIDDEN as $forbidden) {
                if ($normalized === $forbidden || str_ends_with($normalized, '_' . $forbidden)) {
                    throw new \InvalidArgumentException('Sensitive audit metadata is prohibited.');
                }
            }
            if (is_array($value)) { $this->assertSafe($value); }
        }
    }
}
