<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class EmployeeEmergencyContact
{
 public function __construct(public string $publicId,public string $firstName,public string $lastName,public string $relationship,public string $primaryPhone,public ?string $alternatePhone,public ?string $updatedAt=null){}
 public static function fromRow(array $row):self{return new self((string)$row['public_id'],(string)$row['first_name'],(string)$row['last_name'],(string)$row['relationship'],(string)$row['primary_phone'],$row['alternate_phone']===null?null:(string)$row['alternate_phone'],isset($row['updated_at'])?(string)$row['updated_at']:null);}
 public function formData():array{return ['first_name'=>$this->firstName,'last_name'=>$this->lastName,'relationship'=>$this->relationship,'primary_phone'=>$this->primaryPhone,'alternate_phone'=>$this->alternatePhone??''];}
}
