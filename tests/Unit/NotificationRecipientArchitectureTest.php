<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class NotificationRecipientArchitectureTest extends TestCase
{
 public function testPublishersUseCentralResolverAndSendersDoNotResolveEmployees():void
 {$root=dirname(__DIR__,2);foreach(['app/Services/NotificationPublisher.php','app/Services/CompanyNoticePublicationService.php'] as $file)self::assertStringContainsString('NotificationRecipientResolver',(string)file_get_contents($root.'/'.$file));foreach(['app/Services/CompanyAnnouncementDispatchService.php','app/Notifications/CompanyNoticeEmailSender.php'] as $file)self::assertStringNotContainsString('employees',(string)file_get_contents($root.'/'.$file));}
 public function testOnlyApprovedRepositoryOwnsAudienceQuery():void
 {$root=dirname(__DIR__,2);$matches=[];foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app')) as $file)if($file->isFile()&&$file->getExtension()==='php'&&str_contains((string)file_get_contents($file->getPathname()),'employment_status=\'active\''))$matches[]=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));self::assertContains('app/Repositories/NotificationRecipientRepository.php',$matches);foreach($matches as $match)if(str_contains((string)file_get_contents($root.'/'.$match),'notification'))self::assertSame('app/Repositories/NotificationRecipientRepository.php',$match);}
}
