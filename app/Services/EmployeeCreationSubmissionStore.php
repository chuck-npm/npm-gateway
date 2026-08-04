<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Support\PublicIdGenerator;
final class EmployeeCreationSubmissionStore
{
    private const TTL=1800;
    private array $session;
    /** @var \Closure():int */ private readonly \Closure $now;
    public function __construct(array &$session,private readonly PublicIdGenerator $ids,?\Closure $now=null){$this->session=&$session;$this->now=$now??static fn():int=>time();}
    public function create(int $owner):string{$this->cleanup();$token=$this->ids->generate();$this->session['employee_creation_submissions'][$token]=['owner'=>$owner,'state'=>'created','expires_at'=>($this->now)()+self::TTL,'result'=>null];return $token;}
    /** @return array{status:string,result:?array} */
    public function begin(string $token,int $owner):array
    {
        if(!preg_match('/^[A-Za-z0-9_-]{20,128}$/D',$token))return ['status'=>'malformed','result'=>null];
        $row=$this->session['employee_creation_submissions'][$token]??null;
        if(!is_array($row)||($row['owner']??null)!==$owner)return ['status'=>'unavailable','result'=>null];
        if(($row['state']??null)==='created'&&(int)($row['expires_at']??0)<=($this->now)()){$this->session['employee_creation_submissions'][$token]['state']='expired';$row['state']='expired';}
        if(($row['state']??null)!=='created')return ['status'=>(string)($row['state']??'unavailable'),'result'=>is_array($row['result']??null)?$row['result']:null];
        $this->session['employee_creation_submissions'][$token]['state']='processing';
        return ['status'=>'processing_started','result'=>null];
    }
    public function restore(string $token,int $owner):void{if(($this->session['employee_creation_submissions'][$token]['owner']??null)===$owner&&($this->session['employee_creation_submissions'][$token]['state']??null)==='processing')$this->session['employee_creation_submissions'][$token]['state']='created';}
    public function committed(string $token,string $employeePublicId):void{$this->finish($token,'committed',['employee_public_id'=>$employeePublicId]);}
    public function complete(string $token,string $employeePublicId):void{$this->finish($token,'completed',['employee_public_id'=>$employeePublicId]);}
    public function fail(string $token):void{if(($this->session['employee_creation_submissions'][$token]['state']??null)==='processing')$this->session['employee_creation_submissions'][$token]['state']='failed';}
    private function finish(string $token,string $state,array $result):void{if(in_array($this->session['employee_creation_submissions'][$token]['state']??null,['processing','committed'],true)){$this->session['employee_creation_submissions'][$token]['state']=$state;$this->session['employee_creation_submissions'][$token]['result']=$result;$this->session['employee_creation_submissions'][$token]['expires_at']=($this->now)()+self::TTL;}}
    private function cleanup():void{if(!isset($this->session['employee_creation_submissions'])||!is_array($this->session['employee_creation_submissions'])){$this->session['employee_creation_submissions']=[];return;}foreach($this->session['employee_creation_submissions'] as $token=>$row)if(!is_array($row)||(int)($row['expires_at']??0)+self::TTL<($this->now)())unset($this->session['employee_creation_submissions'][$token]);}
}
