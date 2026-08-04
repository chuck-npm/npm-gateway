<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\EmployeeAnnouncement;
interface CompanyAnnouncementEmailSenderInterface { public function send(string $email,EmployeeAnnouncement $announcement):bool; }
