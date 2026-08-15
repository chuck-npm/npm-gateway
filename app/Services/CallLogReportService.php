<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Repositories\CallLogReportRepository;
final readonly class CallLogReportService
{
 public function __construct(private CallLogReportRepository$repo,private CallLogReportDateRangeFactory$ranges){}
 public function facebookPerformance(array$query):array
 {
  $range=$this->ranges->fromQuery($query);$rows=$range->valid()?$this->repo->facebookPerformance($range->fromDate,$range->toExclusive):[];$total=0;$no=0;$answered=0;
  foreach($rows as&$row){$row['total_calls']=(int)$row['total_calls'];$row['no_answer']=(int)$row['no_answer'];$row['answered']=(int)$row['answered'];$row['percent_answered']=$row['total_calls']===0?null:(int)round($row['answered']/$row['total_calls']*100);$total+=$row['total_calls'];$no+=$row['no_answer'];$answered+=$row['answered'];}unset($row);
  return['range'=>$range,'rows'=>$rows,'totals'=>['total_calls'=>$total,'no_answer'=>$no,'answered'=>$answered,'percent_answered'=>$total===0?null:(int)round($answered/$total*100)],'period'=>$range->valid()?$this->period($range->fromDate,$range->toDate):''];
 }
 private function period(string$from,string$to):string{$a=new \DateTimeImmutable($from);$b=new \DateTimeImmutable($to);if($from===$to)return$a->format('F j, Y');if($a->format('Y-m')===$b->format('Y-m'))return$a->format('F j').'–'.$b->format('j, Y');if($a->format('Y')===$b->format('Y'))return$a->format('F j').'–'.$b->format('F j, Y');return$a->format('F j, Y').'–'.$b->format('F j, Y');}
}
