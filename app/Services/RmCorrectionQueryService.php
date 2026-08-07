<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Repositories\RmCorrectionRepository;
final readonly class RmCorrectionQueryService
{
 public function __construct(private RmCorrectionRepository $repo){}
 public function managerList(int $id,string $status='',string $search=''):array{return $this->repo->managerList($id,$status,$search);}
 public function managerDetail(string $public,int $id):?array{return $this->repo->managerDetail($public,$id);}
 public function corporateList(string $status='',string $property='',string $search=''):array{return $this->repo->corporateList($status,$property,$search);}
 public function corporateDetail(string $public):?array{return $this->repo->corporateDetail($public);}
 public function counts(?int $id=null):array{return $this->repo->counts($id);}
 public function properties():array{return $this->repo->properties();}
}
