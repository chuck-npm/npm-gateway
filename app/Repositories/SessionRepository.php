<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
final class SessionRepository
{
 public function __construct(private readonly mysqli $connection){}
 /** @param array<string,mixed> $session */
 public function insert(array $session):void
 {
  $s=$this->connection->prepare('INSERT INTO user_sessions (public_id,user_id,session_token_hash,ip_hash,user_agent,last_activity_at,idle_expires_at,absolute_expires_at,rotated_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
  $s->bind_param('sissssssss',$session['public_id'],$session['user_id'],$session['session_token_hash'],$session['ip_hash'],$session['user_agent'],$session['last_activity_at'],$session['idle_expires_at'],$session['absolute_expires_at'],$session['rotated_at'],$session['created_at']);$s->execute();$s->close();
 }
 /** @return array<string,mixed>|null */
 public function findByHash(string $hash):?array
 {
  $s=$this->connection->prepare('SELECT * FROM user_sessions WHERE session_token_hash=? LIMIT 1');$s->bind_param('s',$hash);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();return is_array($row)?$row:null;
 }
 public function refresh(int $id,string $activity,string $idle):void
 {
  $s=$this->connection->prepare('UPDATE user_sessions SET last_activity_at=?,idle_expires_at=? WHERE id=? AND revoked_at IS NULL');$s->bind_param('ssi',$activity,$idle,$id);$s->execute();$s->close();
 }
 public function rotate(int $id,string $oldHash,string $newHash,string $at,string $idle):bool
 {
  $s=$this->connection->prepare('UPDATE user_sessions SET session_token_hash=?,rotated_at=?,last_activity_at=?,idle_expires_at=? WHERE id=? AND session_token_hash=? AND revoked_at IS NULL');
  $s->bind_param('ssssis',$newHash,$at,$at,$idle,$id,$oldHash);$s->execute();$ok=$s->affected_rows===1;$s->close();return $ok;
 }
 public function revokeByHash(string $hash,string $at,string $reason,?int $by=null):void
 {
  $s=$this->connection->prepare('UPDATE user_sessions SET revoked_at=?,revocation_reason=?,revoked_by=? WHERE session_token_hash=? AND revoked_at IS NULL');
  $s->bind_param('ssis',$at,$reason,$by,$hash);$s->execute();$s->close();
 }
 public function revokeById(int $id,string $at,string $reason):void
 {
  $s=$this->connection->prepare('UPDATE user_sessions SET revoked_at=?,revocation_reason=? WHERE id=? AND revoked_at IS NULL');$s->bind_param('ssi',$at,$reason,$id);$s->execute();$s->close();
 }
}
