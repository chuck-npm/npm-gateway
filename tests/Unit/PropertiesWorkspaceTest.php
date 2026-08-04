<?php
declare(strict_types=1);
use NpmGateway\Contracts\PropertyStoreInterface;
use NpmGateway\Database\Migration\MigrationDiscovery;
use NpmGateway\Database\Migration\MigrationInterface;
use NpmGateway\Services\PropertyValidator;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\Support\PropertyAddressFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class PropertiesWorkspaceTest extends TestCase
{
    public function testMigrationAddsOnlyApprovedNullableColumnsAndConstraints():void
    {
        $path=dirname(__DIR__,2).'/database/migrations/202607310003_properties_workspace.php';$sql=(string)file_get_contents($path);
        self::assertTrue(MigrationDiscovery::isValidFilename(basename($path)));self::assertInstanceOf(MigrationInterface::class,require $path);
        foreach(['prop_id INT UNSIGNED NULL','office_phone VARCHAR(20) NULL','ivr_routing_email VARCHAR(254) NULL','UNIQUE KEY uq_properties_prop_id','chk_properties_prop_id_positive','chk_properties_ivr_routing_email_lowercase','active_primary_manager_property_id BIGINT UNSIGNED GENERATED ALWAYS','active_primary_manager_employee_id BIGINT UNSIGNED GENERATED ALWAYS','uq_assignments_active_primary_manager_property','uq_assignments_active_primary_manager_employee','GROUP BY property_id HAVING COUNT(*)>1','GROUP BY employee_id HAVING COUNT(*)>1'] as $value)self::assertStringContainsString($value,$sql);
        self::assertStringNotContainsString('BIGINT UNSIGNED NULL',$sql);self::assertStringNotContainsString('NOT NULL COMMENT',$sql);
        $down=substr($sql,(int)strpos($sql,'public function down'));foreach(['DROP CHECK chk_properties_ivr_routing_email_lowercase','DROP CHECK chk_properties_prop_id_positive','DROP INDEX uq_properties_prop_id','DROP COLUMN ivr_routing_email','DROP COLUMN office_phone','DROP COLUMN prop_id'] as $value)self::assertStringContainsString($value,$down);
        foreach(['DROP INDEX uq_assignments_active_primary_manager_employee','DROP INDEX uq_assignments_active_primary_manager_property','DROP COLUMN active_primary_manager_employee_id','DROP COLUMN active_primary_manager_property_id'] as $value)self::assertStringContainsString($value,$down);
    }
    public function testManagerQueryUsesOnlyAuthoritativeAssignmentWithoutRecencyFallback():void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Repositories/PropertyRepository.php');self::assertStringContainsString("a.assignment_type='property_manager' AND a.is_primary=1 AND a.ends_on IS NULL AND e.employment_status='active'",$source);self::assertStringNotContainsString('ORDER BY a.is_primary',$source);self::assertStringNotContainsString('a.starts_on DESC LIMIT 1',$source);
    }
    public function testAddressFormatterProducesOneCleanCopyableValue():void
    {
        $formatter=new PropertyAddressFormatter();self::assertSame('609 Dewey St., Sylvester, GA 31791',$formatter->format('609 Dewey St.','Sylvester','GA','31791'));self::assertSame('609 Dewey St., GA 31791',$formatter->format('609 Dewey St.','','GA','31791'));self::assertSame('Sylvester, GA 31791',$formatter->format(null,'Sylvester','GA','31791'));self::assertSame('609 Dewey St., Sylvester, GA 31791-1234',$formatter->format('609 Dewey St.','Sylvester','GA','31791-1234'));
    }
    public function testValidPropertyIsNormalizedWithoutGeneratingIdentifiers():void
    {
        [$value,$errors]=(new PropertyValidator($this->store(),new PhoneFormatter()))->validate($this->valid());self::assertSame([],$errors);self::assertSame(1529,$value['prop_id']);self::assertSame('BT',$value['property_code']);self::assertSame('boulder-trails',$value['slug']);self::assertSame('+12294495184',$value['office_phone']);self::assertSame('+12293544477',$value['ivr_number']);self::assertSame('America/New_York',$value['timezone']);self::assertArrayNotHasKey('public_id',$value);self::assertArrayNotHasKey('manager_name',$value);
    }
    #[DataProvider('invalidCases')]
    public function testInvalidPropertyFieldsFailClosed(string $field,mixed $value):void
    {
        $input=$this->valid();$input[$field]=$value;[, $errors]=(new PropertyValidator($this->store(),new PhoneFormatter()))->validate($input);self::assertArrayHasKey($field,$errors);
    }
    public static function invalidCases():iterable
    {
        yield ['prop_id',''];yield ['prop_id','0'];yield ['prop_id','-1'];yield ['prop_id','abc'];yield ['property_code','B'];yield ['property_code','BTT'];yield ['property_code','B1'];yield ['property_code','B-'];yield ['property_code',' B'];yield ['display_name',''];yield ['slug',''];yield ['slug','Boulder Trails'];yield ['status','sold'];yield ['address_line_1',''];yield ['city',''];yield ['state','XX'];yield ['postal_code','3179'];yield ['timezone','EST'];yield ['office_phone','123'];yield ['manager_email','bad'];yield ['website_url','ftp://example.test'];yield ['ivr_number','123'];yield ['ivr_routing_email','bad'];
    }
    public function testDuplicatePermanentIdentifiersHaveSafeFieldErrors():void
    {
        $store=$this->store(1529,'BT','boulder-trails');[, $errors]=(new PropertyValidator($store,new PhoneFormatter()))->validate($this->valid());self::assertSame('That PropID is already assigned to another property.',$errors['prop_id']);self::assertSame('That property code is already assigned to another property.',$errors['property_code']);self::assertSame('That slug is already assigned to another property.',$errors['slug']);
    }
    public function testNormalAdministrationRejectsAllCorporateIdentifiers():void
    {
        $validator=new PropertyValidator($this->store(),new PhoneFormatter());foreach([['prop_id','1'],['property_code','CO'],['slug','corporate']] as [$field,$value]){$input=$this->valid();$input[$field]=$value;[, $errors]=$validator->validate($input);self::assertArrayHasKey($field,$errors);self::assertStringContainsString('Corporate operational context',$errors[$field]);}
    }
    public function testCommunityPropertiesStillRequireBothIvrValues():void
    {
        $input=$this->valid();$input['ivr_number']='';$input['ivr_routing_email']='';[, $errors]=(new PropertyValidator($this->store(),new PhoneFormatter()))->validate($input);self::assertArrayHasKey('ivr_number',$errors);self::assertArrayHasKey('ivr_routing_email',$errors);
    }
    public function testSchemaVerifierRequiresPropertyManagerOnlyGeneratedExpressions():void{$source=(string)file_get_contents(dirname(__DIR__,2).'/app/Database/Migration/SchemaVerifier.php');foreach(['active_primary_manager_property_id','active_primary_manager_employee_id','property_manager','ends_onisnull','assistant_manager','Invalid generated expression'] as $required)self::assertStringContainsString($required,$source);}
    private function valid():array{return ['prop_id'=>'1529','property_code'=>'bt','display_name'=>'Boulder Trails MHC','slug'=>'boulder-trails','status'=>'active','address_line_1'=>'609 Dewey St.','city'=>'Sylvester','state'=>'ga','postal_code'=>'31791','timezone'=>'America/New_York','office_phone'=>'(229) 449-5184','manager_email'=>'Manager@BoulderTrailsMHC.com','website_url'=>'https://bouldertrailsmhc.com','ivr_number'=>'(229) 354-4477','ivr_routing_email'=>'2419@rentertext.com'];}
    private function store(?int $prop=null,?string $code=null,?string $slug=null):PropertyStoreInterface{return new class($prop,$code,$slug) implements PropertyStoreInterface{public function __construct(private ?int $prop,private ?string $code,private ?string $slug){}public function propIdExists(int $id):bool{return $id===$this->prop;}public function propertyCodeExists(string $code):bool{return $code===$this->code;}public function slugExists(string $slug):bool{return $slug===$this->slug;}public function managerEmailExists(string $email):bool{return false;}public function ivrNumberExists(string $number):bool{return false;}public function insert(array $property):int{return 1;}};}
}
