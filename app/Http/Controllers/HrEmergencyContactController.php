<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\CorporateAccessService;
use NpmGateway\Services\EciReminderService;
use NpmGateway\Services\EmployeeEmergencyContactService;
use NpmGateway\Support\FlashSession;
final class HrEmergencyContactController
{
 public function __construct(private readonly CorporateAccessService $access,private readonly object $contacts,private readonly EmployeeEmergencyContactService $service,private readonly EciReminderService $reminders,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly string $views){}
 public function index(Request $request,AuthenticatedRequestContext $context):Response{if(!$this->allowed($context))return $this->denied();['status'=>$status,'employeeClass'=>$employeeClass,'search'=>$search]=self::filters($request);$rows=$this->contacts->activeEmployees($status,$employeeClass,$search);$summary=['completed'=>0,'missing'=>0];foreach($rows as &$row){$eciStatus=$row['eci_status']==='completed'?'completed':'missing';$summary[$eciStatus]++;$row['reminder']=$this->reminders->availability((string)$row['employee_public_id']);}unset($row);return $this->render('index',$context,compact('rows','summary','status','employeeClass','search'));}
 public function show(string $publicId,AuthenticatedRequestContext $context):Response{if(!$this->allowed($context))return $this->denied();$employee=$this->contacts->findEmployeeByPublicId($publicId);if($employee===null)return $this->missing();$contact=$this->contacts->findByEmployeeId((int)$employee['id']);if($contact===null)return $this->missing();$this->service->recordHrView($context->user,$employee);return $this->render('show',$context,compact('employee','contact'));}
 public function remind(Request $request,string $publicId,AuthenticatedRequestContext $context):Response{if(!$this->allowed($context))return $this->denied();if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.',['Cache-Control'=>'private, no-store']);$result=$this->reminders->send($publicId,$context->user);$messages=['sent'=>'ECI reminder sent.','cooldown'=>'An ECI reminder was already sent within the last 24 hours.','completed'=>'Emergency Contact Information is already complete.','ineligible'=>'This employee is not eligible for ECI reminders.','missing_property'=>'No current primary property is configured.','missing_manager_mailbox'=>'No manager mailbox is configured for this employee’s primary property.','missing_email'=>'No valid business email is available.','inactive'=>'Inactive employees cannot be reminded.','failed'=>'The ECI reminder could not be sent safely.','not_found'=>'Employee not found.'];$this->flash->put('hr_eci_success',$messages[$result['status']]??'The reminder request could not be completed.');return Response::redirect('/corporate/human-resources/emergency-contacts');}
 private function render(string $view,AuthenticatedRequestContext $context,array $vars):Response{extract($vars,EXTR_SKIP);$user=$context->user;$csrfToken=$this->csrf->token();$logoutCsrfToken=$csrfToken;$success=(string)$this->flash->pull('hr_eci_success','');$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/human-resources/emergency-contacts/'.$view.'.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 private static function filters(Request $request):array{$requestedStatus=$request->query['status']??'';$requestedClass=$request->query['employee_class']??'';$requestedSearch=$request->query['search']??'';$status=is_string($requestedStatus)&&in_array($requestedStatus,['missing','completed'],true)?$requestedStatus:'all';$employeeClass=is_string($requestedClass)&&in_array($requestedClass,['corporate','manager','maintenance'],true)?$requestedClass:'all';$search=is_string($requestedSearch)?mb_substr(trim($requestedSearch),0,100):'';return compact('status','employeeClass','search');}
 private function allowed(AuthenticatedRequestContext $context):bool{return $this->access->canAccessCategory($context,'human-resources');}
 private function denied():Response{return new Response(403,'Forbidden',['Cache-Control'=>'private, no-store']);}
 private function missing():Response{return new Response(404,'Not Found',['Cache-Control'=>'private, no-store']);}
}
