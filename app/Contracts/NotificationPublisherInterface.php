<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
interface NotificationPublisherInterface { public function publish(EmployeeAnnouncement $announcement,int $actorUserId,int $actorEmployeeId,string $actorPublicId):array; }
