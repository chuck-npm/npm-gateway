<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Contracts\CompanyAnnouncementEmailSenderInterface;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
final class DisabledCompanyAnnouncementEmailSender implements CompanyAnnouncementEmailSenderInterface { public function send(string $email,EmployeeAnnouncement $announcement):bool{return false;} }
