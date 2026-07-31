<?php
declare(strict_types=1);
use NpmGateway\Contracts\AuditStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\CorporateContextStoreInterface;
use NpmGateway\Contracts\PropertyDirectoryStoreInterface;
use NpmGateway\Contracts\PropertyTransactionInterface;
use NpmGateway\Exceptions\Domain\CorporateContextConflictException;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\CorporateContextSeeder;
use NpmGateway\Services\CorporateContextService;
use NpmGateway\Services\PropertyQueryService;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\Support\PropertyAddressFormatter;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\PropertyDirectoryCriteria;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class CorporateContextTest extends TestCase
{
    public function testSeederCreatesExactCorporateRecordAndAuditThenIsIdempotent():void
    {
        $store=$this->store([]);$audits=new class implements AuditStoreInterface{public array $events=[];public function insert(array $event):void{$this->events[]=$event;}};$service=new CorporateContextService($store,new PublicIdGenerator(),new AuditService($audits,new PublicIdGenerator()),$this->clock());$transaction=$this->transaction();$seeder=new CorporateContextSeeder($transaction,$service);$first=$seeder->seed('test');self::assertTrue($first->created);self::assertCount(1,$store->inserted);$p=$store->inserted[0];self::assertSame(1,$p['prop_id']);self::assertSame('CO',$p['property_code']);self::assertSame('corporate',$p['slug']);self::assertSame('Corporate',$p['display_name']);self::assertSame('active',$p['status']);self::assertSame('5021 River Rd., Ste. C',$p['address_line_1']);self::assertSame('+17065874386',$p['office_phone']);self::assertNull($p['ivr_number']);self::assertNull($p['ivr_routing_email']);self::assertSame('system.corporate_context_created',$audits->events[0]['event_type']);$store->matches=[['id'=>1,'public_id'=>$first->publicId,'prop_id'=>1,'property_code'=>'CO','slug'=>'corporate']];$second=$seeder->seed('test');self::assertFalse($second->created);self::assertCount(1,$store->inserted);self::assertCount(1,$audits->events);self::assertSame(2,$transaction->commits);
    }
    #[DataProvider('conflicts')]
    public function testIdentifierAndSplitConflictsFailClosed(array $matches,string $fragment):void
    {
        $store=$this->store($matches);$service=new CorporateContextService($store,new PublicIdGenerator(),new AuditService(new class implements AuditStoreInterface{public function insert(array $event):void{}},new PublicIdGenerator()),$this->clock());try{$service->ensure('test');self::fail('Expected conflict.');}catch(CorporateContextConflictException $e){self::assertStringContainsString($fragment,$e->getMessage());}self::assertSame([],$store->inserted);
    }
    public static function conflicts():iterable{yield 'prop id'=>[[['id'=>2,'public_id'=>'x','prop_id'=>1,'property_code'=>'XX','slug'=>'other']],'PropID 1'];yield 'code'=>[[['id'=>2,'public_id'=>'x','prop_id'=>2,'property_code'=>'CO','slug'=>'other']],'property code CO'];yield 'slug'=>[[['id'=>2,'public_id'=>'x','prop_id'=>2,'property_code'=>'XX','slug'=>'corporate']],'slug corporate'];yield 'split'=>[[['id'=>2,'public_id'=>'x','prop_id'=>1,'property_code'=>'XX','slug'=>'other'],['id'=>3,'public_id'=>'y','prop_id'=>3,'property_code'=>'CO','slug'=>'corporate']],'PropID 1'];}
    public function testDirectoryFormattingShowsApprovedUnavailableIvrAndNoMailboxManager():void
    {
        $directory=new class implements PropertyDirectoryStoreInterface{public function searchDirectory(PropertyDirectoryCriteria $criteria):array{return [['prop_id'=>1,'display_name'=>'Corporate','address_line_1'=>'5021 River Rd., Ste. C','city'=>'Columbus','state'=>'GA','postal_code'=>'31904','office_phone'=>'+17065874386','ivr_number'=>null,'manager_name'=>'Not assigned']];}public function countDirectoryResults(PropertyDirectoryCriteria $criteria):int{return 1;}};$row=(new PropertyQueryService($directory,new PropertyAddressFormatter(),new PhoneFormatter()))->search(new PropertyDirectoryCriteria())->properties[0];self::assertSame('5021 River Rd., Ste. C, Columbus, GA 31904',$row->address);self::assertSame('(706) 587-4386',$row->phone);self::assertSame('—',$row->ivr);self::assertSame('Not assigned',$row->manager);self::assertNotSame('noc@npmparks.com',$row->manager);
    }
    private function store(array $matches):CorporateContextStoreInterface{return new class($matches) implements CorporateContextStoreInterface{public array $inserted=[];public function __construct(public array $matches){}public function findCorporateIdentifierMatches():array{return $this->matches;}public function insertCorporate(array $property):int{$this->inserted[]=$property;return 1;}};}
    private function clock():ClockInterface{return new class implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-07-31 12:00:00');}};}
    private function transaction():object{return new class implements PropertyTransactionInterface{public int $commits=0;public function begin():void{}public function commit():void{$this->commits++;}public function rollback():void{}};}
}
