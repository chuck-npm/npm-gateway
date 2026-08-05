<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Exceptions\Domain\InvalidEmergencyContactException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\EmployeeEmergencyContactService;
use NpmGateway\Services\ManagerMaintenanceEciAccessService;
use NpmGateway\Support\FlashSession;
use NpmGateway\Support\PhoneFormatter;
final class ManagerMaintenanceEmergencyContactController
{
 public function __construct(private readonly ManagerMaintenanceEciAccessService $access,private readonly EmployeeEmergencyContactService $service,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly PhoneFormatter $phones,private readonly string $views){}
 public function edit(string $publicId,AuthenticatedRequestContext $context):Response{$employee=$this->authorized($publicId,$context);if($employee===null)return $this->missing();$contact=$this->access->contactFor($employee);$errors=(array)$this->flash->pull('manager_eci_errors',[]);$input=(array)$this->flash->pull('manager_eci_input',$contact?->formData()??[]);foreach(['primary_phone','alternate_phone'] as $field)if(isset($input[$field])&&is_string($input[$field]))$input[$field]=$this->phones->format($input[$field]);return $this->render($context,compact('employee','contact','errors','input'));}
 public function save(Request $request,string $publicId,AuthenticatedRequestContext $context):Response{if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.',['Cache-Control'=>'private, no-store']);$employee=$this->authorized($publicId,$context);if($employee===null)return $this->missing();try{$this->service->saveForMaintenanceByManager($context->user,$employee,$request->post);$this->flash->put('manager_eci_success','Emergency contact information saved.');return Response::redirect('/manager/maintenance/'.$publicId.'/emergency-contact');}catch(InvalidEmergencyContactException $e){$this->flash->put('manager_eci_errors',$e->errors);$this->flash->put('manager_eci_input',$e->input);return Response::redirect('/manager/maintenance/'.$publicId.'/emergency-contact');}}
 private function authorized(string $publicId,AuthenticatedRequestContext $context):?array{return $this->access->authorizedTarget($context->user,$publicId);}
 private function render(AuthenticatedRequestContext $context,array $vars):Response{extract($vars,EXTR_SKIP);$user=$context->user;$csrfToken=$this->csrf->token();$logoutCsrfToken=$csrfToken;$success=(string)$this->flash->pull('manager_eci_success','');$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/manager/maintenance-emergency-contact.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 private function missing():Response{return new Response(404,'Not Found',['Cache-Control'=>'private, no-store']);}
}
