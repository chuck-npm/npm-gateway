<?php
declare(strict_types=1);
namespace NpmGateway\Http\Controllers;
use NpmGateway\Contracts\CorporateToolsProviderInterface;
use NpmGateway\Domain\EmployeeClass;
use NpmGateway\Exceptions\Domain\InvalidEmergencyContactException;
use NpmGateway\Http\AuthenticatedRequestContext;
use NpmGateway\Http\Request;
use NpmGateway\Http\Response;
use NpmGateway\Security\CsrfService;
use NpmGateway\Services\EmployeeEmergencyContactService;
use NpmGateway\Support\FlashSession;
use NpmGateway\Support\PhoneFormatter;
final class EmployeeEmergencyContactController
{
 public function __construct(private readonly EmployeeEmergencyContactService $service,private readonly CorporateToolsProviderInterface $tools,private readonly CsrfService $csrf,private readonly FlashSession $flash,private readonly PhoneFormatter $phones,private readonly string $views){}
 public function edit(AuthenticatedRequestContext $context):Response
 {if(!$this->allowed($context))return new Response(403,'Forbidden',['Cache-Control'=>'private, no-store']);try{$contact=$this->service->findFor($context->user);}catch(\DomainException){return new Response(403,'Account context unavailable.',['Cache-Control'=>'private, no-store']);}$user=$context->user;$logoutCsrfToken=$this->csrf->token();$csrfToken=$this->csrf->token();$errors=(array)$this->flash->pull('emergency_contact_errors',[]);$input=(array)$this->flash->pull('emergency_contact_input',$contact?->formData()??[]);foreach(['primary_phone','alternate_phone'] as $field)if(isset($input[$field])&&is_string($input[$field]))$input[$field]=$this->phones->format($input[$field]);$success=(string)$this->flash->pull('emergency_contact_success','');$navbarCorporateItems=$this->tools->tools($context);ob_start();require $this->views.'/my/emergency-contact.php';return new Response(200,(string)ob_get_clean(),['Cache-Control'=>'private, no-store']);}
 public function save(Request $request,AuthenticatedRequestContext $context):Response
 {if(!$this->allowed($context))return new Response(403,'Forbidden',['Cache-Control'=>'private, no-store']);if(!$this->csrf->valid($request->post['_token']??null))return new Response(419,'Invalid request.',['Cache-Control'=>'private, no-store']);try{$this->service->save($context->user,$request->post);$this->flash->put('emergency_contact_success','Emergency contact information updated.');return Response::redirect('/my/emergency-contact');}catch(InvalidEmergencyContactException $e){$this->flash->put('emergency_contact_errors',$e->errors);$this->flash->put('emergency_contact_input',$e->input);return Response::redirect('/my/emergency-contact');}catch(\DomainException){return new Response(403,'Account context unavailable.',['Cache-Control'=>'private, no-store']);}catch(\Throwable){return new Response(500,'Unable to save emergency contact information safely.',['Cache-Control'=>'private, no-store']);}}
 private function allowed(AuthenticatedRequestContext $context):bool{return in_array($context->user->employeeClass,EmployeeClass::NOTIFICATION_ELIGIBLE,true);}
}
