<?php
declare(strict_types=1);
namespace NpmGateway\Exceptions\Domain;
final class InvalidApplicationReviewException extends \DomainException
{
 public function __construct(public readonly array $errors,public readonly array $input){parent::__construct('Application review validation failed.');}
}
