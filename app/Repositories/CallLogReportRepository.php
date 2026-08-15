<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
final readonly class CallLogReportRepository
{
 public const OWNER_PROPERTY_CODES=['BT','CF','FF','HR','MW','PP','PH','PM','SM','WP'];
 public function __construct(private \mysqli$db){}
 public function facebookPerformance(string$from,string$toExclusive):array
 {
  $codes=self::OWNER_PROPERTY_CODES;$marks=implode(',',array_fill(0,count($codes),'?'));$order=[];foreach($codes as$i=>$code)$order[]='WHEN ? THEN '.($i+1);
  $sql="SELECT p.property_code,p.display_name property_name,COALESCE(a.total_calls,0) total_calls,COALESCE(a.no_answer,0) no_answer,COALESCE(a.answered,0) answered FROM properties p LEFT JOIN call_log_destinations d ON d.property_id=p.id AND d.active=1 LEFT JOIN (SELECT destination_id,COUNT(*) total_calls,SUM(CASE WHEN call_duration_seconds < 35.000 THEN 1 ELSE 0 END) no_answer,SUM(CASE WHEN call_duration_seconds >= 35.000 THEN 1 ELSE 0 END) answered FROM call_logs WHERE started_at>=? AND started_at<? GROUP BY destination_id) a ON a.destination_id=d.id WHERE p.status='active' AND p.property_code IN ({$marks}) ORDER BY CASE p.property_code ".implode(' ',$order).' ELSE 999 END';
  $values=[$from,$toExclusive,...$codes,...$codes];$types='ss'.str_repeat('s',count($codes)*2);$s=$this->db->prepare($sql);$s->bind_param($types,...$values);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return$rows;
 }
}
