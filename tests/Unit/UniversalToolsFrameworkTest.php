<?php
declare(strict_types=1);
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\Services\DashboardHomeService;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Services\UniversalToolProvider;
use NpmGateway\Services\CorporateToolsProvider;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\ToolCard;
use NpmGateway\Http\AuthenticatedRequestContext;
use PHPUnit\Framework\TestCase;
final class UniversalToolsFrameworkTest extends TestCase
{
    public function testToolCardIsImmutableAndValidatesDestinations():void
    {
        $disabled=new ToolCard('valid-key','Title','Description','Category','Planned',null,false,10);
        self::assertFalse($disabled->enabled);self::assertTrue((new ReflectionClass($disabled))->isReadOnly());
        $enabled=new ToolCard('real-tool','Real tool','Description','Category','Open tool','/real-tool',true,20,null,'Open real tool','real-tool.index');
        self::assertSame('/real-tool',$enabled->route);
        foreach([
            fn()=>new ToolCard('Bad Key','Title','Description','Category','Planned',null,false,1),
            fn()=>new ToolCard('missing-title','','Description','Category','Planned',null,false,1),
            fn()=>new ToolCard('missing-description','Title','','Category','Planned',null,false,1),
            fn()=>new ToolCard('enabled-no-route','Title','Description','Category','Open',null,true,1,null,null,'tool.index'),
            fn()=>new ToolCard('disabled-route','Title','Description','Category','Planned','/route',false,1),
            fn()=>new ToolCard('external','Title','Description','Category','Open','https://example.com',true,1,null,null,'tool.index'),
            fn()=>new ToolCard('javascript','Title','Description','Category','Open','javascript:alert(1)',true,1,null,null,'tool.index'),
        ] as $invalid){try{$invalid();self::fail('Expected invalid card.');}catch(InvalidArgumentException){}}
    }
    public function testProviderReturnsApprovedDeterministicCatalogWithoutDestinations():void
    {
        $provider=new UniversalToolProvider();$first=$provider->tools();$second=$provider->tools();
        self::assertCount(12,$first);self::assertEquals($first,$second);
        $keys=array_map(fn(ToolCard $card)=>$card->key,$first);
        self::assertCount(12,array_unique($keys));self::assertSame(['employee-directory','property-information','company-documents','announcements','credit-card-purchases','large-file-transfers','order-supplies','time-off-requests','policies-procedures','training-library','support-requests','help-desk'],$keys);
        foreach($first as $index=>$card){if($index===0){self::assertTrue($card->enabled);self::assertSame('/employees',$card->route);self::assertSame('employees.index',$card->routeName);self::assertSame('Open directory',$card->footerLabel);}else{self::assertFalse($card->enabled);self::assertNull($card->route);self::assertNull($card->routeName);}self::assertNotSame('',$card->title);self::assertNotSame('',$card->description);self::assertNotSame('',$card->categoryLabel);self::assertSame(($index+1)*10,$card->sortOrder);}
        self::assertCount(1,array_filter($first,fn(ToolCard $card)=>$card->enabled));
        self::assertCount(0,(new ReflectionClass($provider))->getConstructor()?->getParameters()??[]);
    }
    public function testHomeUsesAuthenticatedEmployeeContextAndTruthfulSummary():void
    {
        $store=new class implements DashboardSummaryStoreInterface{public function counts():array{return ['property_count'=>0,'employee_count'=>1,'user_count'=>1,'active_user_count'=>1,'active_assignment_count'=>0];}};
        $user=new AuthenticatedUser(1,2,'user-public','employee-public','admin','A <User>','Sysadmin','corporate');
        $home=(new DashboardHomeService(new DashboardSummaryService($store),new UniversalToolProvider(),new CorporateToolsProvider(),new CorporateAccessService([])))->forRequest(new AuthenticatedRequestContext($user,'TEST-token'));
        self::assertSame('A <User>',$home->welcomeName);self::assertSame('Corporate',$home->employeeClassLabel);self::assertSame('Sysadmin',$home->jobTitle);self::assertCount(12,$home->universalTools);self::assertTrue($home->setupSummary->initialSetup);self::assertTrue((new ReflectionClass($home))->isReadOnly());
        foreach(['username','email','phone','password','session'] as $field)self::assertFalse(property_exists($home,$field));
    }
    public function testArchitectureKeepsDefinitionsOutOfControllerAndViews():void
    {
        $root=dirname(__DIR__,2);$controller=file_get_contents($root.'/app/Http/Controllers/DashboardController.php');$provider=file_get_contents($root.'/app/Services/UniversalToolProvider.php');
        self::assertStringNotContainsString('Company Directory',$controller);self::assertStringNotContainsString('Company Directory',file_get_contents($root.'/resources/views/dashboard.php'));
        self::assertDoesNotMatchRegularExpression('/\\b(?:SELECT|INSERT|UPDATE|DELETE)\\b/i',$provider);self::assertStringNotContainsString('Repositories\\',$provider);
        self::assertFileDoesNotExist($root.'/database/migrations/202607270003_universal_tools.php');
    }
}
