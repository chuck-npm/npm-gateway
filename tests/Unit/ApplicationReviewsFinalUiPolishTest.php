<?php
declare(strict_types=1);
namespace NpmGateway\Tests\Unit;
use PHPUnit\Framework\TestCase;
final class ApplicationReviewsFinalUiPolishTest extends TestCase
{
 public function testManagerAndCorporateQueuesUseSharedReadableStatusBadges():void
 {
  $root=dirname(__DIR__,2);foreach(['community-actions/application-reviews/index.php','corporate/application-reviews/index.php'] as $relative){$view=(string)file_get_contents($root.'/resources/views/'.$relative);foreach(["['pending_review'=>'Pending Review','approved'=>'Approved','denied'=>'Denied']","['pending_review'=>'neutral','approved'=>'success','denied'=>'danger']","require \$components.'/status-badge.php'",'Pending Review','Approved','Denied'] as $required)self::assertStringContainsString($required,$view);foreach(['<strong>Pending Review','Pending</strong>','Accepted','Rejected','<svg'] as $forbidden)self::assertStringNotContainsString($forbidden,$view);}
  $corporate=(string)file_get_contents($root.'/resources/views/corporate/application-reviews/index.php');foreach(['gateway-review-summary d-flex flex-wrap gap-2',"\$counts[\$key].' '.\$label",'btn btn-sm btn-outline-primary'] as $required)self::assertStringContainsString($required,$corporate);
 }
 public function testManagerDetailUsesPageHeaderStatusThenPropertyWithoutCardDuplicate():void
 {
  $root=dirname(__DIR__,2);$header=(string)file_get_contents($root.'/resources/views/components/page-header.php');$title=strpos($header,'gateway-page-header__title');$status=strpos($header,'gateway-page-header__status');$description=strpos($header,'gateway-page-header__description');self::assertLessThan($status,$title);self::assertLessThan($description,$status);
  $manager=(string)file_get_contents($root.'/resources/views/community-actions/application-reviews/show.php');foreach(["\$heading='Application Review'","\$description=\$context->propertyDisplayName","\$statusHtml=(string)ob_get_clean()","if(\$success!=='')",'gateway-alert--success','breadcrumb.php'] as $required)self::assertStringContainsString($required,$manager);$controller=(string)file_get_contents($root.'/app/Http/Controllers/ApplicationReviewController.php');foreach(['Application review submitted successfully.','Application review submitted, but the review email could not be delivered.'] as $message)self::assertStringContainsString($message,$controller);
  $detail=(string)file_get_contents($root.'/resources/views/components/application-review-detail.php');self::assertStringNotContainsString('gateway-review-status',$detail);self::assertStringNotContainsString("status-badge.php",$detail);self::assertStringContainsString('Current Status',$detail);
 }
 public function testCorporateDecisionWordingContractAndActionOrderAreApproved():void
 {
  $root=dirname(__DIR__,2);$view=(string)file_get_contents($root.'/resources/views/corporate/application-reviews/show.php');foreach(['>Review Decision</h2>','for="reviewer_comments">Review Notes</label>','id="reviewer_comments"','name="reviewer_comments"','maxlength="5000"',' required','btn btn-success','value="approved">Approve','btn btn-danger','value="denied">Deny','btn btn-secondary gateway-review-decision-actions__cancel','>Cancel</a>','data-processing-form'] as $required)self::assertStringContainsString($required,$view);self::assertStringNotContainsString('>Decision</h2>',$view);self::assertStringNotContainsString('for="reviewer_comments">Reviewer Comments</label>',$view);
  $approve=strpos($view,'>Approve</button>');$deny=strpos($view,'>Deny</button>');$cancel=strpos($view,'>Cancel</a>');self::assertLessThan($deny,$approve);self::assertLessThan($cancel,$deny);
  $css=(string)file_get_contents($root.'/public/assets/css/gateway.css');foreach(['.gateway-review-decision-actions { display:flex;flex-wrap:wrap;gap:.75rem;align-items:center; }','.gateway-review-decision-actions__cancel { margin-left:auto; }','.gateway-review-decision-actions__cancel { width:100%;margin-left:0; }'] as $required)self::assertStringContainsString($required,$css);
 }
 public function testInternalDecisionAndPrivacyContractsRemainUntouched():void
 {
  $root=dirname(__DIR__,2);$controller=(string)file_get_contents($root.'/app/Http/Controllers/CorporateApplicationReviewController.php');$validator=(string)file_get_contents($root.'/app/Services/ApplicationReviewValidator.php');foreach(["['Cache-Control'=>'private, no-store']","name=\"decision\" value=\"approved\"","name=\"decision\" value=\"denied\""] as $required)self::assertStringContainsString($required,$controller.(string)file_get_contents($root.'/resources/views/corporate/application-reviews/show.php'));self::assertStringContainsString("['approved','denied']",$validator);
 }
 public function testDecisionSubmissionFeedbackAndProcessingContractAreComplete():void
 {
  $root=dirname(__DIR__,2);$view=(string)file_get_contents($root.'/resources/views/corporate/application-reviews/show.php');foreach(['method="post"','/corporate/application-reviews/<?=$e($review[\'public_id\'])?>/decision','name="_token"','name="reviewer_comments"','<?=$e($input[\'reviewer_comments\']??\'\')?>','name="decision" value="approved"','name="decision" value="denied"','data-processing-message="Saving decision…"','<?=$e($message)?>','invalid-feedback'] as $required)self::assertStringContainsString($required,$view);self::assertSame(1,substr_count($view,'<form '));self::assertSame(1,substr_count($view,'</form>'));self::assertStringNotContainsString('<script',$view);
  $validator=(string)file_get_contents($root.'/app/Services/ApplicationReviewValidator.php');foreach(["['approved','denied']",'Review Notes are required before approving or denying an application.','Review Notes must be plain text of 5,000 characters or fewer.'] as $required)self::assertStringContainsString($required,$validator);
  $controller=(string)file_get_contents($root.'/app/Http/Controllers/CorporateApplicationReviewController.php');foreach(["Application review '.\$decision.' successfully.","Application review '.\$decision.', but the decision email could not be delivered.",'This application review has already been completed.','application_review_decision_input','application_review_decision_errors','application_review_decision_error','Response::redirect'] as $required)self::assertStringContainsString($required,$controller);
  $javascript=(string)file_get_contents($root.'/public/assets/js/processing-overlay.js');foreach(['event.submitter?.form === form','control === submitter','processingClickedSubmitter','aria-disabled','form.dataset.processingSubmitted'] as $required)self::assertStringContainsString($required,$javascript);foreach(['processing-submitter-proxy','createElement(\'input\')','submitter.disabled = true'] as $forbidden)self::assertStringNotContainsString($forbidden,$javascript);$footer=(string)file_get_contents($root.'/resources/views/components/footer.php');self::assertStringContainsString('/assets/js/processing-overlay.js?v=',$footer);self::assertStringContainsString('filemtime($processingOverlayPath)',$footer);
 }
 public function testReviewNotesFieldContractIsIdenticalAtControllerValidationAndRepopulationBoundaries():void
 {
  $root=dirname(__DIR__,2);$view=(string)file_get_contents($root.'/resources/views/corporate/application-reviews/show.php');
  foreach(['for="reviewer_comments"','id="reviewer_comments"','name="reviewer_comments"','aria-describedby="reviewer_comments_error"','id="reviewer_comments_error"',"\$input['reviewer_comments']??''",'htmlspecialchars'] as $required)self::assertStringContainsString($required,$view);
  $controller=(string)file_get_contents($root.'/app/Http/Controllers/CorporateApplicationReviewController.php');
  self::assertStringContainsString("'reviewer_comments'=>\$r->post['reviewer_comments']??null",$controller);
  self::assertStringContainsString("\$this->service->decide(\$publicId,\$decisionPost,\$c->user)",$controller);
  $validator=(string)file_get_contents($root.'/app/Services/ApplicationReviewValidator.php');
  self::assertStringContainsString("\$post['reviewer_comments']??''",$validator);
 }
}
