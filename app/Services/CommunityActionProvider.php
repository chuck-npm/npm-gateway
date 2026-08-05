<?php
declare(strict_types=1);
namespace NpmGateway\Services;

final class CommunityActionProvider
{
    private const ACTIONS = [
        ['key'=>'application-reviews','route_segment'=>'application-reviews','label'=>'Application Reviews','description'=>'Review and process community applications.','order'=>1,'implemented'=>true],
        ['key'=>'credit-card-purchases','route_segment'=>'credit-card-purchases','label'=>'Credit Card Purchases','description'=>'Submit and review property credit card purchases.','order'=>2,'implemented'=>false],
        ['key'=>'rm-corrections','route_segment'=>'rm-corrections','label'=>'RM Corrections','description'=>'Submit Rent Manager correction requests.','order'=>3,'implemented'=>false],
        ['key'=>'renovation-request','route_segment'=>'renovation-requests','label'=>'Renovation Request','description'=>'Request approval for home renovation or repair work.','order'=>4,'implemented'=>false],
        ['key'=>'request-appliances','route_segment'=>'request-appliances','label'=>'Request Appliances','description'=>'Request appliances for community homes.','order'=>5,'implemented'=>false],
        ['key'=>'appliance-distribution','route_segment'=>'appliance-distribution','label'=>'Appliance Distribution','description'=>'Record and manage appliance distribution.','order'=>6,'implemented'=>false],
        ['key'=>'hvac-service-request','route_segment'=>'hvac-service-requests','label'=>'HVAC Service Request','description'=>'Submit HVAC service and repair requests.','order'=>7,'implemented'=>false],
        ['key'=>'order-supplies','route_segment'=>'order-supplies','label'=>'Order Supplies','description'=>'Request supplies for the community.','order'=>8,'implemented'=>false],
        ['key'=>'eviction-checks','route_segment'=>'eviction-checks','label'=>'Eviction Checks','description'=>'Submit and review eviction-related checks.','order'=>9,'implemented'=>false],
        ['key'=>'rm-audit','route_segment'=>'rm-audit','label'=>'RM Audit','description'=>'Review and complete Rent Manager audit tasks.','order'=>10,'implemented'=>false],
    ];

    public function actions(): array { return self::ACTIONS; }

    public function findByRouteSegment(string $segment): ?array
    {
        foreach (self::ACTIONS as $action) if ($action['route_segment'] === $segment) return $action;
        return null;
    }
}
