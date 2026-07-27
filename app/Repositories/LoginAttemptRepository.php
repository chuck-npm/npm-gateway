<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
final class LoginAttemptRepository
{
 public function __construct(private readonly mysqli $connection){}
 public function countRecentFailuresByIp(string $ipHash,string $since):int
 {
  $s=$this->connection->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_hash=? AND was_successful=0 AND attempted_at>=?');
  $s->bind_param('ss',$ipHash,$since);$s->execute();$count=(int)$s->get_result()->fetch_row()[0];$s->close();return $count;
 }
 /** @param array<string,mixed> $attempt */
 public function insert(array $attempt):void
 {
  $s=$this->connection->prepare('INSERT INTO login_attempts (public_id,submitted_username_hash,user_id,was_successful,failure_reason,ip_hash,user_agent,attempted_at) VALUES (?,?,?,?,?,?,?,?)');
  $s->bind_param('ssiissss',$attempt['public_id'],$attempt['submitted_username_hash'],$attempt['user_id'],$attempt['was_successful'],$attempt['failure_reason'],$attempt['ip_hash'],$attempt['user_agent'],$attempt['attempted_at']);$s->execute();$s->close();
 }
}
