<?php
declare(strict_types=1);
namespace NpmGateway\Contracts;
use NpmGateway\ValueObjects\HrEmployeeNotice;
interface HrEmployeeNotifierInterface{public function notify(HrEmployeeNotice $notice):void;}
