<?php declare(strict_types=1);namespace NpmGateway\Services;
use NpmGateway\Repositories\RmAuditRepository;use NpmGateway\ValueObjects\RmAuditOverviewCriteria;
final readonly class OperationsRmAuditOverviewService
{
 public function __construct(private RmAuditRepository $repo){}
 public function properties():array{return$this->repo->properties();}
 public function report(RmAuditOverviewCriteria $criteria):array
 {
  $rows=$criteria->errors===[]?$this->repo->overviewList($criteria->startBoundary,$criteria->endBoundary,$criteria->propertyPublicId,$criteria->status==='open'):[];$groups=[];$counts=['open'=>0,'completed'=>0];
  foreach($rows as&$row){$row['operations_status']=OperationsRmAuditStatus::project($row['status']);$counts[$row['operations_status']]++;$groups[$row['property_name']][]=$row;}unset($row);
  return['rows'=>$rows,'groups'=>$groups,'counts'=>$counts,'total'=>count($rows)];
 }
 public function detail(string $publicId):?array{$audit=$this->repo->detail($publicId);if($audit!==null)$audit['operations_status']=OperationsRmAuditStatus::project($audit['status']);return$audit;}
}
