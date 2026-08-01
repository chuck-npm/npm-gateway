<?php
declare(strict_types=1);
use NpmGateway\Services\PropertyDirectoryCriteriaFactory;
use NpmGateway\Contracts\AuditStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\PropertyDirectoryStoreInterface;
use NpmGateway\Contracts\PropertyStoreInterface;
use NpmGateway\Contracts\PropertyTransactionInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Controllers\PropertyWorkspaceController;
use NpmGateway\Http\Request;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Contracts\CategoryAccessStoreInterface;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\Services\PropertyAdministrationService;
use NpmGateway\Services\PropertyQueryService;
use NpmGateway\Services\PropertyValidator;
use NpmGateway\Support\FlashSession;
use NpmGateway\Support\PhoneFormatter;
use NpmGateway\Support\PropertyAddressFormatter;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
final class PropertyDirectoryCriteriaFactoryTest extends TestCase
{
    public function testEmptyQueryUsesSafeExplicitDefaultsWithoutWarnings():void
    {
        $warnings=[];set_error_handler(static function(int $severity,string $message)use(&$warnings):bool{$warnings[]=$message;return true;});try{$criteria=(new PropertyDirectoryCriteriaFactory())->fromQuery([]);}finally{restore_error_handler();}
        self::assertSame([],$warnings);self::assertSame('',$criteria->search);self::assertSame('prop_id',$criteria->sort);self::assertSame('asc',$criteria->direction);self::assertSame(1,$criteria->page);self::assertSame(25,$criteria->perPage);
    }
    public function testValidValuesAreNormalizedAndPreserved():void
    {
        $criteria=(new PropertyDirectoryCriteriaFactory())->fromQuery(['search'=>'  Boulder Trails  ','sort'=>'manager','direction'=>'DESC','page'=>'3','per_page'=>'50']);self::assertSame('Boulder Trails',$criteria->search);self::assertSame('manager',$criteria->sort);self::assertSame('desc',$criteria->direction);self::assertSame(3,$criteria->page);self::assertSame(50,$criteria->perPage);
    }
    #[DataProvider('fallbackCases')]
    public function testInvalidOrNonScalarValuesFallBackWithoutWarnings(string $key,mixed $value,string $property,mixed $expected):void
    {
        $warnings=[];set_error_handler(static function(int $severity,string $message)use(&$warnings):bool{$warnings[]=$message;return true;});try{$criteria=(new PropertyDirectoryCriteriaFactory())->fromQuery([$key=>$value]);}finally{restore_error_handler();}self::assertSame([],$warnings);self::assertSame($expected,$criteria->{$property});
    }
    public static function fallbackCases():iterable
    {
        yield 'unsupported sort'=>['sort','unknown','sort','prop_id'];yield 'missing sort via array'=>['sort',['name'],'sort','prop_id'];yield 'unsupported direction'=>['direction','sideways','direction','asc'];yield 'array direction'=>['direction',['desc'],'direction','asc'];yield 'zero page'=>['page','0','page',1];yield 'negative page'=>['page','-4','page',1];yield 'invalid page text'=>['page','two','page',1];yield 'array page'=>['page',['2'],'page',1];yield 'unsupported per page'=>['per_page','5000','perPage',25];yield 'array per page'=>['per_page',['50'],'perPage',25];yield 'array search'=>['search',['Boulder'],'search',''];
    }
    public function testBothPropertyRoutesRenderWithoutQueryParameters():void
    {
        $controller=$this->controller();$context=new AuthenticatedRequestContext(new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),'listed','Listed User','Administrator','corporate'),'token');self::assertSame(200,$controller->directory(new Request('GET','/properties'),$context)->status);self::assertSame(200,$controller->hrDirectory(new Request('GET','/human-resources/properties'),$context)->status);
    }
    public function testCreateRouteAuthorizationAndValidationPrgBehavior():void
    {
        $controller=$this->controller();$authorized=$this->context('listed');$page=$controller->create($authorized);self::assertSame(200,$page->status);self::assertStringContainsString('Add Property',$page->body);preg_match('/name="_token" value="([a-f0-9]+)"/',$page->body,$match);self::assertNotEmpty($match[1]??'');$response=$controller->store(new Request('POST','/human-resources/properties',['_token'=>$match[1],'display_name'=>'Safe Name','state'=>'GA','timezone'=>'America/Chicago','status'=>'inactive']),$authorized);self::assertSame(303,$response->status);self::assertSame('/human-resources/properties/create',$response->headers['Location']);$invalid=$controller->create($authorized);self::assertStringContainsString('property-validation-summary',$invalid->body);self::assertStringContainsString('value="Safe Name"',$invalid->body);self::assertStringContainsString('value="GA" selected',$invalid->body);self::assertStringContainsString('value="America/Chicago" selected',$invalid->body);self::assertStringContainsString('value="inactive" selected',$invalid->body);self::assertStringNotContainsString('modal',$invalid->body);self::assertSame(403,$controller->create($this->context('unlisted'))->status);
    }
    public function testSuccessfulCreateRedirectsToIndexWithSuccessFlash():void
    {
        $controller=$this->controller();$context=$this->context('listed');$page=$controller->create($context);preg_match('/name="_token" value="([a-f0-9]+)"/',$page->body,$match);$post=['_token'=>$match[1],'prop_id'=>'1529','property_code'=>'BT','display_name'=>'Boulder Trails MHC','slug'=>'boulder-trails','status'=>'active','address_line_1'=>'609 Dewey St.','city'=>'Sylvester','state'=>'GA','postal_code'=>'31791','timezone'=>'America/New_York','office_phone'=>'(229) 449-5184','manager_email'=>'manager@bouldertrailsmhc.com','website_url'=>'https://bouldertrailsmhc.com','ivr_number'=>'(229) 354-4477','ivr_routing_email'=>'2419@rentertext.com'];$response=$controller->store(new Request('POST','/human-resources/properties',$post),$context);self::assertSame('/human-resources/properties',$response->headers['Location']);$index=$controller->hrDirectory(new Request('GET','/human-resources/properties'),$context);self::assertStringContainsString('Property added successfully.',$index->body);
    }
    public function testRenderedCreatePageHasTwoMaskedPhoneInputsAndSpaciousHeader():void
    {
        $page=$this->controller()->create($this->context('listed'));self::assertSame(2,substr_count($page->body,'data-phone-mask'));self::assertSame(2,substr_count($page->body,'inputmode="tel"'));self::assertStringContainsString('gateway-page-header--spacious',$page->body);self::assertStringContainsString('autocomplete="tel"',$page->body);self::assertStringContainsString('autocomplete="off"',$page->body);
    }
    private function controller():PropertyWorkspaceController
    {
        $store=new class implements PropertyStoreInterface,PropertyDirectoryStoreInterface{public function propIdExists(int $id):bool{return false;}public function propertyCodeExists(string $code):bool{return false;}public function slugExists(string $slug):bool{return false;}public function managerEmailExists(string $email):bool{return false;}public function ivrNumberExists(string $number):bool{return false;}public function insert(array $property):int{return 1;}public function searchDirectory(\NpmGateway\ValueObjects\PropertyDirectoryCriteria $criteria):array{return [];}public function countDirectoryResults(\NpmGateway\ValueObjects\PropertyDirectoryCriteria $criteria):int{return 0;}};
        $transaction=new class implements PropertyTransactionInterface{public function begin():void{}public function commit():void{}public function rollback():void{}};$audits=new class implements AuditStoreInterface{public function insert(array $event):void{}};$clock=new class implements ClockInterface{public function now():DateTimeImmutable{return new DateTimeImmutable('2026-07-31 12:00:00');}};$phones=new PhoneFormatter();$admin=new PropertyAdministrationService(new PropertyValidator($store,$phones),$store,$transaction,new PublicIdGenerator(),new AuditService($audits,new PublicIdGenerator()),$clock);$session=[];$accessStore=new class implements CategoryAccessStoreInterface{public function hasEffectiveMembership(int $userId,string $category):bool{return $userId===1&&$category==='human-resources';}public function findUserByUsername(string $username):?array{return null;}public function allUsers():array{return [];}public function memberships():array{return [];}public function grant(array $membership):void{}public function revoke(int $userId,string $category):void{}};$config=require dirname(__DIR__,2).'/config/corporate-access.php';$access=new CorporateAccessService($accessStore,$config['categories']);return new PropertyWorkspaceController(new PropertyDirectoryCriteriaFactory(),new PropertyQueryService($store,new PropertyAddressFormatter(),$phones),$admin,$access,new CorporateToolsProvider($access),new CsrfService($session),new FlashSession($session),dirname(__DIR__,2).'/resources/views');
    }
    private function context(string $username):AuthenticatedRequestContext{return new AuthenticatedRequestContext(new AuthenticatedUser($username==='listed'?1:9,2,str_repeat('U',26),str_repeat('E',26),$username,'Listed User','Administrator','corporate'),'token');}
}
