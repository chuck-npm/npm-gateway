<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class HrEmployeeCreationResult{public function __construct(public bool $notificationSent,public bool $noticePublished=false,public int $announcementFailures=0) {}}
