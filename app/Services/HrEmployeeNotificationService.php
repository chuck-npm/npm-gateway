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
        $subject='SECURE — New Gateway Employee: '.$data['first_name'].' '.$data['last_name'];
        $birthDate=\DateTimeImmutable::createFromFormat('!Y-m-d',(string)($data['date_of_birth']??''));
        $data['date_of_birth']=$birthDate?$birthDate->format('m/d/Y'):'Not provided';
        foreach(['personal_phone','personal_email','comments'] as $key)if(($data[$key]??null)===null||trim((string)$data[$key])==='')$data[$key]='Not provided';
        if($data['personal_phone']!=='Not provided'){$digits=preg_replace('/\D/','',(string)$data['personal_phone'])??'';if(strlen($digits)===11&&$digits[0]==='1')$digits=substr($digits,1);if(strlen($digits)===10)$data['personal_phone']='('.substr($digits,0,3).') '.substr($digits,3,3).'-'.substr($digits,6);}
        $labels=['Employee Number'=>'employee_number','First Name'=>'first_name','Last Name'=>'last_name','Full Name'=>'full_name','Date of Birth'=>'date_of_birth','Job Title'=>'job_title','Employee Type'=>'employee_type_label','Employment Status'=>'employment_status','Start Date'=>'start_date','Company Phone'=>'company_phone','Business Email'=>'business_email','Personal Phone'=>'personal_phone','Personal Email'=>'personal_email','Operational Context'=>'context_name','Property Code'=>'property_code','PropID'=>'prop_id','Assignment Type'=>'assignment_type','Primary Assignment'=>'primary_assignment','Gateway Username'=>'username','Initial Gateway Password'=>'initial_password','Employee Notes'=>'comments','Created By'=>'created_by_name','Created By Gateway Username'=>'created_by_username','Created At'=>'created_at','Gateway Environment'=>'environment'];
        $data['initial_password']=$password;$text="SECURE — New Gateway Employee\n\n";$html='<h1>New Gateway Employee</h1><dl>';
        foreach($labels as $label=>$key){$value=(string)($data[$key]??'');$text.=$label.': '.$value."\n";$html.='<dt>'.htmlspecialchars($label,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</dt><dd>'.nl2br(htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')).'</dd>';}
        $html.='</dl>';$this->notifier->notify(new HrEmployeeNotice($recipients,$from,(string)($this->config['from_name']??'NPM Gateway'),$subject,$html,$text,$password));unset($data['initial_password']);
    }
}
