<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Contracts\SessionTokenGeneratorInterface;
use NpmGateway\Contracts\SessionServiceInterface;
use NpmGateway\Exceptions\Domain\InvalidSessionException;
use NpmGateway\Repositories\SessionRepository;
use NpmGateway\Repositories\UserRepository;
use NpmGateway\Security\AuthenticationHasher;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\AuthenticatedUser;
use NpmGateway\ValueObjects\ClientContext;
use NpmGateway\ValueObjects\SessionToken;
use NpmGateway\ValueObjects\SessionValidationResult;
final class SessionService implements SessionServiceInterface
{
 public function __construct(private readonly SessionRepository $sessions,private readonly UserRepository $users,private readonly SessionTokenGeneratorInterface $tokens,private readonly AuthenticationHasher $hasher,private readonly PublicIdGenerator $ids,private readonly AuthenticationConfig $config,private readonly ?AuditService $audits=null){}
 public function create(AuthenticatedUser $user,ClientContext $context):SessionToken
 {
  $raw=$this->tokens->generate();$public=$this->ids->generate();$now=$context->now;$absolute=$now->modify("+{$this->config->absoluteHours} hours");$idle=$now->modify("+{$this->config->idleMinutes} minutes");if($idle>$absolute)$idle=$absolute;
  $this->sessions->insert(['public_id'=>$public,'user_id'=>$user->id,'session_token_hash'=>$this->hasher->session($raw),'ip_hash'=>$this->hasher->ip($context->ipAddress),'user_agent'=>$this->agent($context->userAgent),'last_activity_at'=>$this->format($now),'idle_expires_at'=>$this->format($idle),'absolute_expires_at'=>$this->format($absolute),'rotated_at'=>$this->format($now),'created_at'=>$this->format($now)]);
  return new SessionToken($raw,$public);
 }
 public function validate(string $raw,ClientContext $context):SessionValidationResult
 {
  if(!preg_match('/^[A-Za-z0-9_-]{43}$/',$raw))throw new InvalidSessionException('Invalid session.');
  $hash=$this->hasher->session($raw);$session=$this->sessions->findByHash($hash);if(!$session||$session['revoked_at']!==null)throw new InvalidSessionException('Invalid session.');
  $now=$context->now;$absolute=new \DateTimeImmutable($session['absolute_expires_at']);$idle=new \DateTimeImmutable($session['idle_expires_at']);
  if($now>=$absolute){$this->sessions->revokeById((int)$session['id'],$this->format($now),'absolute_expired');throw new InvalidSessionException('Invalid session.');}
  if($now>=$idle){$this->sessions->revokeById((int)$session['id'],$this->format($now),'idle_expired');throw new InvalidSessionException('Invalid session.');}
  $record=$this->users->findActiveIdentity((int)$session['user_id']);if(!$record||$record['status']!=='active'||$record['employment_status']!=='active'){$this->sessions->revokeById((int)$session['id'],$this->format($now),'account_disabled');throw new InvalidSessionException('Invalid session.');}
  $user=$this->identity($record);$last=new \DateTimeImmutable($session['last_activity_at']);$rotated=new \DateTimeImmutable($session['rotated_at']);$newIdle=$now->modify("+{$this->config->idleMinutes} minutes");if($newIdle>$absolute)$newIdle=$absolute;
  $replacement=null;
  if($now >= $rotated->modify("+{$this->config->rotationMinutes} minutes")){
   $newRaw=$this->tokens->generate();if(!$this->sessions->rotate((int)$session['id'],$hash,$this->hasher->session($newRaw),$this->format($now),$this->format($newIdle)))throw new InvalidSessionException('Invalid session.');
   $replacement=new SessionToken($newRaw,(string)$session['public_id']);
  } elseif($now >= $last->modify("+{$this->config->activityWriteMinutes} minutes")){$this->sessions->refresh((int)$session['id'],$this->format($now),$this->format($newIdle));}
  return new SessionValidationResult($user,$replacement);
 }
 public function logout(string $raw,ClientContext $context):void
 {
  if(preg_match('/^[A-Za-z0-9_-]{43}$/',$raw)!==1)return;$hash=$this->hasher->session($raw);$session=$this->sessions->findByHash($hash);$this->sessions->revokeByHash($hash,$this->format($context->now),'logout');
  if($session&&$this->audits){$user=$this->users->findActiveIdentity((int)$session['user_id']);if($user){$identity=$this->identity($user);foreach(['authentication.logout','authentication.session_revoked'] as $event)$this->audits->record($event,$identity->id,$identity->employeeId,$identity->publicId,'Gateway session revoked.',['session_public_id'=>$session['public_id'],'reason'=>'logout','revoked_at'=>$this->format($context->now)],$this->format($context->now));}}
 }
 private function identity(array $r):AuthenticatedUser{return new AuthenticatedUser((int)$r['id'],(int)$r['employee_id'],(string)$r['public_id'],(string)$r['employee_public_id'],(string)$r['username'],trim($r['first_name'].' '.$r['last_name']),(string)($r['job_title']??''));}
 private function agent(?string $agent):?string{if($agent===null)return null;return substr(preg_replace('/[\\x00-\\x1F\\x7F]/','',(string)$agent),0,500);}
 private function format(\DateTimeImmutable $date):string{return $date->format('Y-m-d H:i:s');}
}
