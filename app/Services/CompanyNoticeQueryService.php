<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Repositories\NotificationRepository;
use NpmGateway\Repositories\NotificationStorageObjectRepository;
final class CompanyNoticeQueryService { public function __construct(private readonly NotificationRepository $notices,private readonly ?NotificationStorageObjectRepository $assets=null){} public function summaries():array{return $this->notices->companyNoticeSummaries();}public function detail(string $publicId):?array{$row=$this->notices->companyNoticeDetail($publicId);if($row!==null)$row['assets']=$this->assets?->forNotification((int)$row['id'])??[];return $row;}public function companyNoticeSummaries():array{return $this->summaries();}public function companyNoticeDetail(string $publicId):?array{return $this->detail($publicId);} }
