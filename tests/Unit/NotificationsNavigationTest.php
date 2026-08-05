<?php
declare(strict_types=1);
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\NotificationCount;
use PHPUnit\Framework\TestCase;
final class NotificationsNavigationTest extends TestCase
{
 public function testEmptyIndexRendersWithProjectNavigationAndActiveItem():void
 {
  $html=$this->render('index');self::assertStringContainsString('<h1>Notifications</h1>',$html);self::assertStringContainsString('You have no notices requiring acknowledgment.',$html);self::assertStringContainsString('href="/notifications"',$html);self::assertMatchesRegularExpression('/href="\/notifications"[^>]*aria-current="page"/',$html);
 }
 public function testAssignedDetailRendersWithSameActiveProjectNavigation():void
 {
  $html=$this->render('show');self::assertStringContainsString('>New Employee</h1>',$html);self::assertStringContainsString('Kathrina Petty',$html);self::assertStringContainsString('August 2, 2026',$html);self::assertStringContainsString('(555) 123-4567',$html);self::assertStringContainsString('mailto:kathrina@example.test',$html);self::assertStringContainsString('Priority: Normal',$html);self::assertStringNotContainsString('2026-08-02',$html);self::assertStringNotContainsString('+15551234567',$html);self::assertStringContainsString('I Have Read This Notice',$html);self::assertMatchesRegularExpression('/href="\/notifications"[^>]*aria-current="page"/',$html);
 }
 public function testNotificationViewsUseApprovedNestedViewRootAndNoLocalAbsolutePath():void
 {
  $root=dirname(__DIR__,2);foreach(['index.php','show.php'] as $file){$source=(string)file_get_contents($root.'/resources/views/notifications/'.$file);self::assertStringContainsString("Navigation::forRoute('/notifications',dirname(__DIR__,3))",$source);self::assertStringNotContainsString('dirname(__DIR__,2)',$source);self::assertStringNotContainsString('resources/config/navigation.php',str_replace('\\','/',$source));self::assertStringNotContainsString('C:\\xampp',$source);}
  $navigation=(string)file_get_contents($root.'/app/Support/Navigation.php');self::assertStringContainsString('$projectRoot',$navigation);self::assertStringContainsString("\$projectRoot.'/config/navigation.php'",$navigation);
 }
 private function render(string $view):string
 {
  $root=dirname(__DIR__,2);$user=new AuthenticatedUser(1,2,str_repeat('U',26),str_repeat('E',26),'chuck','Chuck Test','Administrator','corporate');$notificationCount=new NotificationCount(0);$navbarCorporateItems=[];$logoutCsrfToken='csrf';$csrfToken='csrf';$success='';$canCreateNotice=false;
  if($view==='index'){$filter='outstanding';$notices=[];}else{$notice=['public_id'=>str_repeat('N',26),'summary'=>'A new employee has joined NPM Properties.','payload'=>['employee_name'=>'Kathrina Petty','job_title'=>'Manager','start_date'=>'2026-08-02','company_phone'=>'+15551234567','business_email'=>'kathrina@example.test','primary_property'=>'Corporate'],'published_at'=>'2026-08-02 03:42:00','priority'=>'normal','acknowledged_at'=>null];$displayPayload=(new \NpmGateway\Services\NotificationPresentationService(new \NpmGateway\Support\CompanyDateFormatter(),new \NpmGateway\Support\PhoneFormatter()))->employeeFields($notice['payload']);}
  if($view==='show'){$notice['notification_type']='employee_created';$notice['title']='New Employee';$notice['requires_acknowledgment']=1;}ob_start();require $root.'/resources/views/notifications/'.$view.'.php';return (string)ob_get_clean();
 }
}
