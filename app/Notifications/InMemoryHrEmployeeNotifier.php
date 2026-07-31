<?php
declare(strict_types=1);
namespace NpmGateway\Notifications;
use NpmGateway\Contracts\HrEmployeeNotifierInterface;
use NpmGateway\ValueObjects\HrEmployeeNotice;
final class InMemoryHrEmployeeNotifier implements HrEmployeeNotifierInterface{public array $notices=[];public function notify(HrEmployeeNotice $notice):void{$this->notices[]=$notice;}}
