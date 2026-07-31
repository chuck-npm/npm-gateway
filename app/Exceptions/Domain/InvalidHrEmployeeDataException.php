<?php
declare(strict_types=1);
namespace NpmGateway\Exceptions\Domain;
final class InvalidHrEmployeeDataException extends \RuntimeException
{
    public function __construct(public readonly array $errors,public readonly array $input){parent::__construct('Employee data is invalid.');}
}
