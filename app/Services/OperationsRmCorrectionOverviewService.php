<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Repositories\RmCorrectionRepository;
use NpmGateway\ValueObjects\RmCorrectionOverviewCriteria;
final readonly class OperationsRmCorrectionOverviewService
{
 public function __construct(private RmCorrectionRepository $repo){}
 public function properties():array{return $this->repo->properties();}
 public function report(RmCorrectionOverviewCriteria $criteria):array
 {
  $rows=$criteria->errors===[]?$this->repo->overviewList($criteria->startBoundary,$criteria->endBoundary,$criteria->propertyPublicId):[];$groups=[];$counts=array_fill_keys(['pending_review','approved','denied','more_information_needed'],0);
  foreach($rows as $row){$counts[$row['status']]++;$groups[$row['property_name']][]=$row;}return ['rows'=>$rows,'groups'=>$groups,'counts'=>$counts,'total'=>count($rows)];
 }
 public function detail(string $publicId):?array{return $this->repo->corporateDetail($publicId);}
}
