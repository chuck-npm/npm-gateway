<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use NpmGateway\Database\Migration\ApplicationReviewsCategorySchema;
use PHPUnit\Framework\TestCase;
final class ApplicationReviewsCategoryTest extends TestCase
{
 public function testCategoryConfigurationAndMigrationContract():void
 {
  $root=dirname(__DIR__,2);$config=require $root.'/config/corporate-access.php';
  self::assertSame(['operations','human-resources','company-notices','application-reviews','rm-corrections','finance','marketing','admin','credit-cards'],array_keys($config['categories']));
  self::assertSame('Application Reviews',$config['categories']['application-reviews']);
  self::assertSame('202608050016_application_reviews_category',ApplicationReviewsCategorySchema::MIGRATION);
  $migration=(string)file_get_contents($root.'/database/migrations/202608050016_application_reviews_category.php');
  foreach(['chk_user_category_access_category',"category='application-reviews'",'assertConstraint','Cannot roll back Application Reviews'] as $required)self::assertStringContainsString($required,$migration);
  foreach(['CREATE TABLE','ADD COLUMN','ADD INDEX','FOREIGN KEY','DELETE FROM','UPDATE user_category_access'] as $forbidden)self::assertStringNotContainsString($forbidden,$migration);
 }
 public function testStandaloneCardRoutesAuthorizationAndOperationsCleanup():void
 {
  $root=dirname(__DIR__,2);$routes=require $root.'/routes/web.php';
  foreach(['/corporate/application-reviews','/corporate/application-reviews/{reviewPublicId}','/corporate/application-reviews/{reviewPublicId}/decision'] as $route){self::assertArrayHasKey($route,$routes);self::assertContains('application-reviews-access',$routes[$route]['middleware']);}
  foreach(['/corporate/operations/application-reviews','/corporate/operations/application-reviews/{reviewPublicId}','/corporate/operations/application-reviews/{reviewPublicId}/decision'] as $old)self::assertArrayNotHasKey($old,$routes);
  $provider=(string)file_get_contents($root.'/app/Services/CorporateToolsProvider.php');self::assertStringContainsString("['application-reviews','Application Reviews','Review prospect submissions from all communities.','Corporate','/corporate/application-reviews'",$provider);
  $operations=(string)file_get_contents($root.'/app/Http/Controllers/CorporateWorkspaceController.php');self::assertStringNotContainsString("new ToolCard('application-reviews'",$operations);
  $controller=(string)file_get_contents($root.'/app/Http/Controllers/CorporateApplicationReviewController.php');self::assertStringContainsString("canAccessCategory(\$c,'application-reviews')",$controller);self::assertStringNotContainsString("canAccessCategory(\$c,'operations')",$controller);
 }
 public function testCorporatePresentationAndEmailUseOnlyNewHierarchy():void
 {
  $root=dirname(__DIR__,2);$views=(string)file_get_contents($root.'/resources/views/corporate/application-reviews/index.php').(string)file_get_contents($root.'/resources/views/corporate/application-reviews/show.php');
  foreach(['Application Reviews','Review prospect submissions from all communities.','Corporate'] as $required)self::assertStringContainsString($required,$views);
  foreach(['/corporate/operations/application-reviews',"['label'=>'Operations'",'<style','style=','<script'] as $forbidden)self::assertStringNotContainsString($forbidden,$views);
  $email=(string)file_get_contents($root.'/app/Notifications/ApplicationReviewEmailSender.php');self::assertStringContainsString("/corporate/application-reviews/",$email);self::assertStringNotContainsString('/corporate/operations/application-reviews',$email);
 }
}
