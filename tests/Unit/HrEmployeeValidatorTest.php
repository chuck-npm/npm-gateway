<?php
declare(strict_types=1);
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\HrEmployeeStoreInterface;
use NpmGateway\Contracts\UserStoreInterface;
use NpmGateway\Exceptions\Domain\InvalidHrEmployeeDataException;
use NpmGateway\Services\HrEmployeeValidator;
use NpmGateway\Support\PhoneFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class HrEmployeeValidatorTest extends TestCase
{
    public function testValidHistoricalDobAndBlankOptionalFieldsAreNormalized():void{$valid=$this->validator()->validate($this->input());self::assertSame('1985-08-14',$valid['date_of_birth']);self::assertNull($valid['personal_phone']);self::assertNull($valid['personal_email']);self::assertNull($valid['comments']);$valid=$this->validator()->validate($this->input(['personal_phone'=>' (706) 555-1212 ','personal_email'=>' Person@Example.Test ','comments'=>" First line\nSecond line "]));self::assertSame('+17065551212',$valid['personal_phone']);self::assertSame('person@example.test',$valid['personal_email']);self::assertSame("First line\nSecond line",$valid['comments']);}
    #[DataProvider('invalidDates')]
    public function testMissingMalformedImpossibleAndFutureDobAreRejected(string $date):void{try{$this->validator()->validate($this->input(['date_of_birth'=>$date]));self::fail('Invalid Date of Birth accepted.');}catch(InvalidHrEmployeeDataException $exception){self::assertArrayHasKey('date_of_birth',$exception->errors);}}
    public static function invalidDates():iterable{yield 'missing'=>[''];yield 'malformed'=>['08/14/1985'];yield 'impossible'=>['2024-02-30'];yield 'future'=>['2026-08-02'];}
    #[DataProvider('invalidOptionalValues')]
    public function testSuppliedOptionalContactValuesStillValidate(string $key,string $value):void{try{$this->validator()->validate($this->input([$key=>$value]));self::fail('Invalid optional value accepted.');}catch(InvalidHrEmployeeDataException $exception){self::assertArrayHasKey($key,$exception->errors);}}
    public static function invalidOptionalValues():iterable{yield 'phone'=>['personal_phone','123'];yield 'email'=>['personal_email',"bad@example.test\r\nBcc:x"];yield 'notes'=>['comments',str_repeat('x',65536)];}
    private function input(array $replace=[]):array{return array_replace(['first_name'=>'Tim','last_name'=>'Tester','job_title'=>'Analyst','employee_type'=>'corporate','employment_status'=>'active','start_date'=>'2026-07-01','date_of_birth'=>'1985-08-14','company_phone'=>'7065551111','business_email'=>'tim@example.test','personal_phone'=>' ','personal_email'=>' ','property_id'=>'','username'=>'timtester','comments'=>' '],$replace);}
    private function validator():HrEmployeeValidator{$employees=new class implements HrEmployeeStoreInterface{public function employeeNumberExists(string $employeeNumber):bool{return false;}public function insert(array $employee):int{return 1;}public function highestEmployeeNumber():?string{return null;}public function eligibleProperties():array{return [];}public function findOperationalProperty(int $id):?array{return null;}public function corporateProperty():?array{return ['id'=>1,'public_id'=>str_repeat('P',26),'prop_id'=>1,'property_code'=>'CO','display_name'=>'Corporate','status'=>'active','slug'=>'corporate'];}public function insertAssignment(array $assignment):int{return 1;}};$users=new class implements UserStoreInterface{public function anyExists():bool{return false;}public function usernameExists(string $username):bool{return false;}public function insert(array $user):int{return 1;}};$clock=new class implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-08-01 12:00:00',new DateTimeZone('America/New_York'));}};return new HrEmployeeValidator($employees,$users,new PhoneFormatter(),$clock);}
}
