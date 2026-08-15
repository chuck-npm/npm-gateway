<?php
declare(strict_types=1);
namespace NpmGateway\ValueObjects;
final readonly class PropertyDirectoryCriteria
{
    public function __construct(public string $search='',public string $sort='name',public string $direction='asc',public int $page=1,public int $perPage=25){}
}
