<?php
declare(strict_types=1);

use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use NpmGateway\Repositories\RmCorrectionRepository;
use NpmGateway\Services\OperationsRmCorrectionOverviewService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\RmCorrectionOverviewCriteria;

$app=require dirname(__DIR__,2).'/bootstrap/app.php';
foreach(['application','migration'] as $profile){
    if(DatabaseProfiles::load($profile,$app['root'])['database']!=='npmgateway_test'){
        fwrite(STDERR,"Operations Overview disposable profile gate failed.\n");
        exit(2);
    }
}
$db=MySqlConnectionFactory::connect(DatabaseProfiles::load('migration',$app['root']));
$ids=new PublicIdGenerator();
$marker='ovcert'.bin2hex(random_bytes(3));
$employeeIds=[];$userIds=[];$propertyIds=[];$requestIds=[];

try{
    $employeePublic=$ids->generate();$employeeNumber='NPM'.random_int(880000,889999);
    $first='Operations';$last='Certification';
    $statement=$db->prepare("INSERT INTO employees(public_id,employee_number,employee_class,first_name,last_name,job_title,employment_status,start_date) VALUES(?,?,'corporate',?,?,'Overview Certification','active','2026-08-01')");
    $statement->bind_param('ssss',$employeePublic,$employeeNumber,$first,$last);$statement->execute();$employeeIds[]=$employeeId=$db->insert_id;$statement->close();
    $userPublic=$ids->generate();$password=password_hash('Disposable-123!',PASSWORD_DEFAULT);
    $statement=$db->prepare("INSERT INTO users(public_id,employee_id,username,password_hash,status) VALUES(?,?,?,?,'active')");
    $statement->bind_param('siss',$userPublic,$employeeId,$marker,$password);$statement->execute();$userIds[]=$userId=$db->insert_id;$statement->close();

    foreach([['Alpha Overview Community','alpha',889901,'active'],['Beta Overview Community','beta',889902,'active'],['Inactive Overview Community','inactive',889903,'inactive']] as [$name,$suffix,$propId,$state]){
        $public=$ids->generate();$slug=$marker.'-'.$suffix;$code=['alpha'=>'QA','beta'=>'QB','inactive'=>'QI'][$suffix];$email=$slug.'@example.test';
        $statement=$db->prepare("INSERT INTO properties(public_id,prop_id,property_code,slug,display_name,status,manager_email,address_line_1,city,state,postal_code,timezone) VALUES(?,?,?,?,?,?,?,'1 Test Way','Scranton','PA','18503','America/New_York')");
        $statement->bind_param('sisssss',$public,$propId,$code,$slug,$name,$state,$email);$statement->execute();$propertyIds[$suffix]=['id'=>$db->insert_id,'public_id'=>$public];$statement->close();
    }

    $fixtures=[
        ['alpha','pending_review','2026-08-01 00:00:00','A1'],
        ['alpha','approved','2026-08-31 23:59:59','A2'],
        ['alpha','denied','2026-08-15 10:00:00','A3'],
        ['beta','more_information_needed','2026-08-20 12:00:00','B1'],
        ['beta','approved','2026-07-31 23:59:59','B2'],
        ['inactive','approved','2026-08-10 12:00:00','I1'],
    ];
    $fixturePublic=[];
    foreach($fixtures as [$propertyKey,$status,$submitted,$lot]){
        $public=$ids->generate();$fixturePublic[]=$public;$tenant='Tenant '.$lot;$request='Certification narrative '.$lot;$propertyId=$propertyIds[$propertyKey]['id'];
        $statement=$db->prepare('INSERT INTO rm_correction_requests(public_id,property_id,submitted_by_user_id,lot_address,tenant_name,correction_request,status,submitted_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
        $statement->bind_param('siissssss',$public,$propertyId,$userId,$lot,$tenant,$request,$status,$submitted,$submitted);$statement->execute();$requestIds[]=$db->insert_id;$statement->close();
    }

    $service=new OperationsRmCorrectionOverviewService(new RmCorrectionRepository($db));
    $properties=$service->properties();
    foreach(['Corporate','Inactive Overview Community'] as $excluded){if(in_array($excluded,array_column($properties,'display_name'),true))throw new RuntimeException('Excluded property appeared in selector.');}
    $criteria=RmCorrectionOverviewCriteria::fromQuery(['from'=>'2026-08-01','to'=>'2026-08-31'],new DateTimeImmutable('2026-08-07'),$properties);
    $report=$service->report($criteria);$fixtureRows=array_values(array_filter($report['rows'],static fn(array $row):bool=>in_array($row['public_id'],$fixturePublic,true)));
    if(count($fixtureRows)!==4)throw new RuntimeException('Date/inactive-property filtering failed.');
    if(array_values(array_unique(array_column($fixtureRows,'property_name')))!==['Alpha Overview Community','Beta Overview Community'])throw new RuntimeException('Property grouping order failed.');
    $alphaRows=array_values(array_filter($fixtureRows,static fn(array $row):bool=>$row['property_name']==='Alpha Overview Community'));
    if(array_column($alphaRows,'lot_address')!==['A2','A3','A1'])throw new RuntimeException('Submission descending order failed.');
    $expected=['pending_review'=>1,'approved'=>1,'denied'=>1,'more_information_needed'=>1];foreach($expected as $status=>$count){$actual=count(array_filter($fixtureRows,static fn(array $row):bool=>$row['status']===$status));if($actual!==$count)throw new RuntimeException('Fixture summary mismatch.');}
    $single=RmCorrectionOverviewCriteria::fromQuery(['from'=>'2026-08-01','to'=>'2026-08-31','property'=>$propertyIds['alpha']['public_id']],new DateTimeImmutable('2026-08-07'),$properties);
    if($service->report($single)['total']!==3)throw new RuntimeException('Single-property filter failed.');
    $detail=$service->detail($fixturePublic[1]);if($detail===null||$detail['property_name']!=='Alpha Overview Community'||$detail['lot_address']!=='A2')throw new RuntimeException('Authoritative detail failed.');
    echo "profiles=npmgateway_test\ncurrent_month_boundaries=passed\ninclusive_to_date=passed\nall_properties_fixture_rows=4\nproperty_groups=Alpha Overview Community,Beta Overview Community\nalpha_order=A2,A3,A1\nstatus_counts=1,1,1,1\nsingle_property_rows=3\nread_only_detail=passed\n";
}catch(Throwable $exception){fwrite(STDERR,str_replace(["\r","\n"],' ',$exception->getMessage())."\n");$exitCode=1;
}finally{
    if($requestIds)$db->query('DELETE FROM rm_correction_history WHERE rm_correction_request_id IN ('.implode(',',$requestIds).')');
    if($requestIds)$db->query('DELETE FROM rm_correction_requests WHERE id IN ('.implode(',',$requestIds).')');
    if($userIds){$list=implode(',',$userIds);$db->query("DELETE FROM user_category_access WHERE user_id IN ({$list}) OR granted_by_user_id IN ({$list})");$db->query("DELETE FROM users WHERE id IN ({$list})");}
    if($propertyIds)$db->query('DELETE FROM properties WHERE id IN ('.implode(',',array_column($propertyIds,'id')).')');
    if($employeeIds)$db->query('DELETE FROM employees WHERE id IN ('.implode(',',$employeeIds).')');
    $safe=$db->real_escape_string($marker);$residue=(int)$db->query("SELECT COUNT(*) FROM users WHERE username='{$safe}'")->fetch_row()[0];echo "fixture_residue={$residue}\n";$db->close();
}
exit($exitCode??0);
