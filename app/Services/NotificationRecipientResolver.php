<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Repositories\NotificationRecipientRepository;
final class NotificationRecipientResolver
{
 public function __construct(private readonly NotificationRecipientRepository $recipients,private readonly GatewayNotificationRecipientEligibilityPolicy $policy){}
 public function resolveCompanyAudience():array{return $this->policy->requireEligibleRecipients($this->recipients->eligibleAudienceCandidates());}
 public function requireApproved(array $recipients):array{return $this->policy->requireEligibleRecipients($recipients);}
 public function resolveTargetedUser(string $userPublicId):?array{$candidate=$this->recipients->targetedCandidate($userPublicId);return $candidate!==null&&$this->policy->isEligibleUser($candidate)?$candidate:null;}
}
