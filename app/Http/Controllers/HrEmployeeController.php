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
use NpmGateway\Support\FlashSession;
final class HrEmployeeController
{
    public function __construct(private readonly EmployeeDirectoryCriteriaFactory $criteria,private readonly EmployeeDirectoryService $directory,private readonly HrEmployeeCreationService $creation,private readonly CorporateAccessService $access,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
    public function index(Request $request,AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$directoryPage=$this->directory->search($this->criteria->fromQuery($request->query));$success=(string)$this->flash->pull('hr_employee_success','');$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/human-resources/employees/index.php';return new Response(200,(string)ob_get_clean());
    }
    public function create(AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');$user=$context->user;$logoutCsrfToken=$this->csrf->token();$csrfToken=$this->csrf->token();$errors=(array)$this->flash->pull('hr_employee_errors',[]);$input=(array)$this->flash->pull('hr_employee_input',[]);if(!isset($input['start_date']))$input['start_date']=$this->creation->currentDate();$properties=$this->creation->eligibleProperties();$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/human-resources/employees/create.php';return new Response(200,(string)ob_get_clean());
    }
    public function store(Request $request,AuthenticatedRequestContext $context):Response
    {
        if(!$this->allowed($context))return new Response(403,'Forbidden');if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.');try{$result=$this->creation->create($request->post,$context->user);$this->flash->put('hr_employee_success',$result->notificationSent?'Employee and Gateway account created successfully. Secure notification sent.':'Employee and Gateway account were created, but the secure notification could not be sent.');return Response::redirect('/human-resources/employees');}catch(InvalidHrEmployeeDataException $e){$this->flash->put('hr_employee_errors',$e->errors);$this->flash->put('hr_employee_input',$e->input);return Response::redirect('/human-resources/employees/create');}
    }
    private function allowed(AuthenticatedRequestContext $context):bool{return $this->access->canAccessCategory($context,'human-resources');}
}
