<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Contracts\AuthenticationServiceInterface;
use NpmGateway\Exceptions\Domain\InvalidCredentialsException;
use NpmGateway\Repositories\LoginAttemptRepository;
use NpmGateway\Repositories\UserRepository;
use NpmGateway\Security\AuthenticationHasher;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\AuthenticationResult;
use NpmGateway\ValueObjects\ClientContext;
use NpmGateway\ValueObjects\LoginRequest;
final class AuthenticationService implements AuthenticationServiceInterface
{
 private string $dummyHash;
 public function __construct(private readonly InitializationTransactionInterface $transaction,private readonly UserRepository $users,private readonly LoginAttemptRepository $attempts,private readonly LoginThrottleService $throttle,private readonly SessionService $sessions,private readonly AuditService $audits,private readonly AuthenticationHasher $hasher,private readonly PublicIdGenerator $ids,private readonly AuthenticationConfig $config)
 { $this->dummyHash=password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT); }
 public function authenticate(LoginRequest $request,ClientContext $context):AuthenticationResult
 {
  $username=strtolower(trim($request->username));$validFormat=preg_match('/^[a-z][a-z0-9]{1,49}$/',$username)===1;$ipHash=$this->hasher->ip($context->ipAddress);$usernameHash=$this->hasher->username($username);$at=$context->now->format('Y-m-d H:i:s');
  $this->transaction->begin();
  try {
   if($this->throttle->limited($ipHash,$context->now)){$this->attempt($usernameHash,null,0,'rate_limited',$ipHash,$context,$at);$this->transaction->commit();throw new InvalidCredentialsException('Authentication failed.');}
   $record=$validFormat?$this->users->findForAuthentication($username):null;$passwordValid=password_verify($request->password(),$record['password_hash']??$this->dummyHash);
   $available=$record&&$record['status']==='active'&&$record['employment_status']==='active';
   $locked=$record&&$record['locked_until']!==null&&new \DateTimeImmutable($record['locked_until'])>$context->now;
   if(!$record||!$passwordValid||!$available||$locked){
    $reason=!$record||!$passwordValid?'invalid_credentials':($locked?'account_locked':'account_disabled');
    if($record&&!$passwordValid&&$available&&!$locked){$count=(int)$record['failed_login_count']+1;$until=$count>=$this->config->maxFailures?$context->now->modify("+{$this->config->lockMinutes} minutes")->format('Y-m-d H:i:s'):null;$this->users->recordFailure((int)$record['id'],$count,$until);if($until!==null)$this->audits->record('authentication.account_locked',(int)$record['id'],(int)$record['employee_id'],(string)$record['public_id'],'Gateway account locked.',['username'=>$record['username'],'locked_until'=>$until],$at);}
    $this->attempt($usernameHash,$record?(int)$record['id']:null,0,$reason,$ipHash,$context,$at);$this->transaction->commit();throw new InvalidCredentialsException('Authentication failed.');
   }
   $user=new AuthenticatedUser((int)$record['id'],(int)$record['employee_id'],(string)$record['public_id'],(string)$record['employee_public_id'],(string)$record['username'],trim($record['first_name'].' '.$record['last_name']),(string)($record['job_title']??''),(string)($record['employee_class']??''));
   $this->users->recordSuccess($user->id,$at);
   if(password_needs_rehash((string)$record['password_hash'],defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT)){$hash=password_hash($request->password(),defined('PASSWORD_ARGON2ID')?PASSWORD_ARGON2ID:PASSWORD_DEFAULT);if(is_string($hash)){try{$this->users->updatePasswordHash($user->id,$hash);}catch(\Throwable){/* Opportunistic rehash must not reject a valid login. */}}}
   $session=$this->sessions->create($user,$context);$this->attempt($usernameHash,$user->id,1,null,$ipHash,$context,$at);
   $this->audits->record('authentication.login_succeeded',$user->id,$user->employeeId,$user->publicId,'Gateway login succeeded.',['username'=>$user->username,'logged_in_at'=>$at],$at);
   $this->transaction->commit();return new AuthenticationResult($user,$session);
  } catch(InvalidCredentialsException $e){throw $e;} catch(\Throwable $e){$this->transaction->rollback();throw new \RuntimeException('Authentication operation failed.');}
 }
 private function attempt(string $usernameHash,?int $userId,int $success,?string $reason,string $ipHash,ClientContext $context,string $at):void{$this->attempts->insert(['public_id'=>$this->ids->generate(),'submitted_username_hash'=>$usernameHash,'user_id'=>$userId,'was_successful'=>$success,'failure_reason'=>$reason,'ip_hash'=>$ipHash,'user_agent'=>$context->userAgent===null?null:substr(preg_replace('/[\\x00-\\x1F\\x7F]/','',$context->userAgent),0,500),'attempted_at'=>$at]);}
}
