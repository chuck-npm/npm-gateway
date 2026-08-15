<?php
declare(strict_types=1);
use NpmGateway\Database\Migration\{MigrationDiscovery,MigrationInterface,SharedPropertyContactsSchema};
use NpmGateway\Services\PropertyAdministrationService;
use PHPUnit\Framework\TestCase;

final class SharedPropertyContactsMigrationTest extends TestCase
{
    public function testMigrationReplacesOnlyManagerEmailUniqueIndex():void
    {
        $path=dirname(__DIR__,2).'/database/migrations/202608140023_shared_property_contacts.php';$source=(string)file_get_contents($path);
        self::assertSame('202608140023_shared_property_contacts',SharedPropertyContactsSchema::MIGRATION);
        self::assertTrue(MigrationDiscovery::isValidFilename(basename($path)));
        self::assertInstanceOf(MigrationInterface::class,require $path);
        self::assertStringContainsString('DROP INDEX uq_properties_manager_email, DROP INDEX uq_properties_ivr_number',$source);
        self::assertStringContainsString('ADD INDEX idx_properties_manager_email (manager_email), ADD INDEX idx_properties_ivr_number (ivr_number)',$source);
        self::assertStringContainsString('DROP INDEX idx_properties_manager_email, DROP INDEX idx_properties_ivr_number',$source);
        self::assertStringContainsString('ADD UNIQUE INDEX uq_properties_manager_email (manager_email), ADD UNIQUE INDEX uq_properties_ivr_number (ivr_number)',$source);
        self::assertStringContainsString('HAVING COUNT(*)>1',$source);
        self::assertStringNotContainsString('office_phone',$source);
        foreach(['uq_properties_public_id','uq_properties_property_code','uq_properties_slug']as$identity)self::assertStringNotContainsString('DROP INDEX '.$identity,$source);
    }

    public function testApplicationValidationTreatsManagerEmailAsContactData():void
    {
        $interface=(string)file_get_contents(dirname(__DIR__,2).'/app/Contracts/PropertyStoreInterface.php');
        $validator=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/PropertyValidator.php');
        $repository=(string)file_get_contents(dirname(__DIR__,2).'/app/Repositories/PropertyRepository.php');
        foreach([$interface,$validator,$repository]as$source){self::assertStringNotContainsString('managerEmailExists',$source);self::assertStringNotContainsString('ivrNumberExists',$source);}
        foreach(['FILTER_VALIDATE_EMAIL','manager_email','office_phone']as$validation)self::assertStringContainsString($validation,$validator);
    }

    public function testDuplicateSqlClassificationNeverDefaultsToPropId():void
    {
        $source=(string)file_get_contents(dirname(__DIR__,2).'/app/Services/PropertyAdministrationService.php');
        foreach(['uq_properties_prop_id','uq_properties_property_code','uq_properties_slug','uq_properties_manager_email','uq_properties_ivr_number']as$index)self::assertStringContainsString($index,$source);
        self::assertStringContainsString("['form'=>'A unique property value is already in use.",$source);
        self::assertStringNotContainsString("'slug':'prop_id'",$source);
    }

    public function testOnlyPropIdIndexProducesPropIdDuplicateError():void
    {
        $service=(new ReflectionClass(PropertyAdministrationService::class))->newInstanceWithoutConstructor();$method=(new ReflectionClass($service))->getMethod('duplicateViolation');
        foreach(['uq_properties_manager_email'=>'manager_email','uq_properties_ivr_number'=>'ivr_number','uq_properties_property_code'=>'property_code','uq_properties_slug'=>'slug','uq_properties_prop_id'=>'prop_id','unknown_unique_index'=>'form']as$index=>$expected){$error=$method->invoke($service,new mysqli_sql_exception("Duplicate entry for key '{$index}'",1062),['prop_id'=>990102]);self::assertSame([$expected],array_keys($error->errors));}
    }
}
