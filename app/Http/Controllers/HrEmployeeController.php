<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Exceptions\Domain\InvalidHrEmployeeDataException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\EmployeeDirectoryCriteriaFactory;
use NpmGateway\Services\EmployeeDirectoryService;
use NpmGateway\Services\HrEmployeeCreationService;
use NpmGateway\Services\EmployeeCreationSubmissionStore;
use NpmGateway\Support\FlashSession;
final class HrEmployeeController
{
    public function __construct(private readonly EmployeeDirectoryCriteriaFactory $criteria,private readonly EmployeeDirectoryService $directory,private readonly HrEmployeeCreationService $creation,private readonly EmployeeCreationSubmissionStore $submissions,private readonly CorporateAccessService $access,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
    public function index(Request $request,AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$directoryPage=$this->directory->search($this->criteria->fromQuery($request->query));$success=(string)$this->flash->pull('hr_employee_success','');$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/human-resources/employees/index.php';return new Response(200,(string)ob_get_clean());
    }
    public function create(AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$csrfToken=$this->csrf->token();$submissionToken=$this->submissions->create($context->user->id);$errors=(array)$this->flash->pull('hr_employee_errors',[]);$input=(array)$this->flash->pull('hr_employee_input',[]);if(!isset($input['start_date']))$input['start_date']=$this->creation->currentDate();$properties=$this->creation->eligibleProperties();$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/human-resources/employees/create.php';return new Response(200,(string)ob_get_clean());
    }
    public function store(Request $request,AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');$token=(string)($request->post['employee_submission_token']??'');$claim=$this->submissions->begin($token,$context->user->id);if(in_array($claim['status'],['completed','committed'],true)){$this->flash->put('hr_employee_success','This employee creation request was already completed.');return Response::redirect('/human-resources/employees');}if($claim['status']==='processing')return new Response(409,'This employee creation request is already processing.');if($claim['status']!=='processing_started'){$this->flash->put('hr_employee_errors',['submission'=>$claim['status']==='expired'?'This employee form has expired. Please review the information and submit again.':'This employee form could not be submitted safely. Please review the information and submit again.']);$safe=$request->post;unset($safe['_token'],$safe['employee_submission_token']);$this->flash->put('hr_employee_input',$safe);return Response::redirect('/human-resources/employees/create');}try{$employeePublicId=null;$result=$this->creation->create($request->post,$context->user,function(string $publicId)use($token,&$employeePublicId):void{$employeePublicId=$publicId;$this->submissions->committed($token,$publicId);});if(!is_string($employeePublicId))throw new \RuntimeException('Employee result unavailable.');$this->submissions->complete($token,$employeePublicId);$message=!$result->notificationSent?'Employee and Gateway account were created, but the secure notification could not be sent.':(!$result->noticePublished?'Employee and Gateway account were created and the secure notification was sent, but the company notice could not be published.':($result->announcementFailures>0?'Employee and Gateway account were created, but one or more company announcement emails could not be delivered. The Gateway notice is available.':'Employee and Gateway account created successfully. Secure notification sent and company notice published.'));$this->flash->put('hr_employee_success',$message);return Response::redirect('/human-resources/employees');}catch(InvalidHrEmployeeDataException $e){$this->submissions->restore($token,$context->user->id);$this->flash->put('hr_employee_errors',$e->errors);$this->flash->put('hr_employee_input',$e->input);return Response::redirect('/human-resources/employees/create');}catch(\Throwable){$this->submissions->fail($token);return new Response(500,'Unable to create the employee safely.');}
    }
    private function allowed(AuthenticatedRequestContext $context):bool{return $this->access->canAccessCategory($context,'human-resources');}
}
