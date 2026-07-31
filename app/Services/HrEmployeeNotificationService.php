<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\HrEmployeeNotifierInterface;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\ValueObjects\HrEmployeeNotice;
final class HrEmployeeNotificationService
{
    public function __construct(private readonly HrEmployeeNotifierInterface $notifier,private readonly array $config){}
    public function validateConfiguration():void{HrEmployeeNotificationConfig::fromArray($this->config);if($this->notifier instanceof \NpmGateway\Notifications\DisabledHrEmployeeNotifier)throw new CredentialNotificationException('HR notification transport is disabled.');}
    public function send(array $data,#[\SensitiveParameter]string $password):void
    {
        $configuration=HrEmployeeNotificationConfig::fromArray($this->config);$recipients=$configuration->recipients;$from=$configuration->fromAddress;
        $subject='SECURE — New Gateway Employee: '.$data['first_name'].' '.$data['last_name'];$labels=['Employee Number'=>'employee_number','First Name'=>'first_name','Last Name'=>'last_name','Full Name'=>'full_name','Job Title'=>'job_title','Employee Type'=>'employee_type_label','Employment Status'=>'employment_status','Start Date'=>'start_date','Company Phone'=>'company_phone','Business Email'=>'business_email','Personal Phone'=>'personal_phone','Personal Email'=>'personal_email','Operational Context'=>'context_name','Property Code'=>'property_code','PropID'=>'prop_id','Assignment Type'=>'assignment_type','Primary Assignment'=>'primary_assignment','Gateway Username'=>'username','Initial Gateway Password'=>'initial_password','Employee Notes'=>'comments','Created By'=>'created_by_name','Created By Gateway Username'=>'created_by_username','Created At'=>'created_at','Gateway Environment'=>'environment'];$data['initial_password']=$password;$text="SECURE — New Gateway Employee\n\n";$html='<h1>New Gateway Employee</h1><dl>';foreach($labels as $label=>$key){$value=(string)($data[$key]??'');$text.=$label.': '.$value."\n";$html.='<dt>'.htmlspecialchars($label,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</dt><dd>'.nl2br(htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')).'</dd>';}$html.='</dl>';$this->notifier->notify(new HrEmployeeNotice($recipients,$from,(string)($this->config['from_name']??'NPM Gateway'),$subject,$html,$text,$password));unset($data['initial_password']);
    }
}
