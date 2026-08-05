<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Repositories\ApplicationReviewRepository;
final readonly class ApplicationReviewQueryService
{
 public function __construct(private ApplicationReviewRepository $repo){}
 public function managerList(int $propertyId,string $status='',string $search=''):array{return $this->repo->managerList($propertyId,$status,$search);}
 public function managerDetail(string $publicId,int $propertyId):?array{return $this->repo->managerDetail($publicId,$propertyId);}
 public function corporateQueue(string $status='',string $propertyPublicId='',string $search=''):array{return $this->repo->corporateQueue($status,$propertyPublicId,$search);}
 public function corporateDetail(string $publicId):?array{return $this->repo->corporateDetail($publicId);}
 public function counts():array{return $this->repo->counts();}
 public function properties():array{return $this->repo->properties();}
}
