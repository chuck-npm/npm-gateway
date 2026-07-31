<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\PropertyDirectoryStoreInterface;
use NpmGateway\Contracts\PropertyStoreInterface;
use NpmGateway\Contracts\CorporateContextStoreInterface;
use NpmGateway\ValueObjects\PropertyDirectoryCriteria;
final class PropertyRepository implements PropertyDirectoryStoreInterface,PropertyStoreInterface,CorporateContextStoreInterface
{
    public function __construct(private readonly mysqli $connection){}
    public function propIdExists(int $propId):bool{return $this->exists('prop_id','i',$propId);}
    public function propertyCodeExists(string $code):bool{return $this->exists('property_code','s',$code);}
    public function slugExists(string $slug):bool{return $this->exists('slug','s',$slug);}
    public function managerEmailExists(string $email):bool{return $this->exists('manager_email','s',$email);}
    public function ivrNumberExists(string $number):bool{return $this->exists('ivr_number','s',$number);}
    private function exists(string $column,string $type,int|string $value):bool{$s=$this->connection->prepare("SELECT 1 FROM properties WHERE {$column}=? LIMIT 1");$s->bind_param($type,$value);$s->execute();$found=$s->get_result()->num_rows>0;$s->close();return $found;}
    public function insert(array $p):int
    {
        $s=$this->connection->prepare('INSERT INTO properties (public_id,prop_id,property_code,slug,display_name,status,office_phone,manager_email,ivr_number,ivr_routing_email,website_url,address_line_1,city,state,postal_code,timezone,created_by,updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $s->bind_param('sissssssssssssssii',$p['public_id'],$p['prop_id'],$p['property_code'],$p['slug'],$p['display_name'],$p['status'],$p['office_phone'],$p['manager_email'],$p['ivr_number'],$p['ivr_routing_email'],$p['website_url'],$p['address_line_1'],$p['city'],$p['state'],$p['postal_code'],$p['timezone'],$p['created_by'],$p['updated_by']);
        $s->execute();$id=(int)$this->connection->insert_id;$s->close();return $id;
    }
    public function findCorporateIdentifierMatches():array
    {
        $result=$this->connection->query("SELECT id,public_id,prop_id,property_code,slug FROM properties WHERE prop_id=1 OR property_code='CO' OR slug='corporate' ORDER BY id");$rows=$result->fetch_all(MYSQLI_ASSOC);$result->free();return $rows;
    }
    public function insertCorporate(array $p):int{return $this->insert($p);}
    public function searchDirectory(PropertyDirectoryCriteria $criteria):array
    {
        [$where,$types,$params]=$this->where($criteria);$orders=['prop_id'=>'p.prop_id','name'=>'p.display_name','address'=>'p.address_line_1, p.city, p.state, p.postal_code','phone'=>'p.office_phone','ivr'=>'p.ivr_number','manager'=>'manager_name'];$order=$orders[$criteria->sort]??$orders['prop_id'];$direction=$criteria->direction==='desc'?'DESC':'ASC';$offset=($criteria->page-1)*$criteria->perPage;
        $sql="SELECT p.prop_id,p.display_name,p.address_line_1,p.city,p.state,p.postal_code,p.office_phone,p.ivr_number,COALESCE((SELECT CONCAT(COALESCE(NULLIF(e.preferred_name,''),e.first_name),' ',e.last_name) FROM employee_property_assignments a JOIN employees e ON e.id=a.employee_id WHERE a.property_id=p.id AND a.assignment_type='property_manager' AND a.is_primary=1 AND a.ends_on IS NULL AND e.employment_status='active'),'Not assigned') manager_name FROM properties p {$where} ORDER BY {$order} {$direction},p.display_name ASC LIMIT ? OFFSET ?";
        $params[]=$criteria->perPage;$params[]=$offset;$types.='ii';$s=$this->connection->prepare($sql);$this->bind($s,$types,$params);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;
    }
    public function countDirectoryResults(PropertyDirectoryCriteria $criteria):int{[$where,$types,$params]=$this->where($criteria);$s=$this->connection->prepare("SELECT COUNT(*) FROM properties p {$where}");$this->bind($s,$types,$params);$s->execute();$count=(int)$s->get_result()->fetch_row()[0];$s->close();return $count;}
    private function where(PropertyDirectoryCriteria $criteria):array
    {
        if($criteria->search==='')return ['','',[]];$term='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$criteria->search).'%';
        $manager="COALESCE((SELECT CONCAT(COALESCE(NULLIF(e.preferred_name,''),e.first_name),' ',e.last_name) FROM employee_property_assignments a JOIN employees e ON e.id=a.employee_id WHERE a.property_id=p.id AND a.assignment_type='property_manager' AND a.is_primary=1 AND a.ends_on IS NULL AND e.employment_status='active'),'Not assigned')";
        return ["WHERE (CAST(p.prop_id AS CHAR) LIKE ? OR p.display_name LIKE ? OR CONCAT_WS(', ',p.address_line_1,p.city,CONCAT_WS(' ',p.state,p.postal_code)) LIKE ? OR p.office_phone LIKE ? OR p.ivr_number LIKE ? OR {$manager} LIKE ?)",'ssssss',array_fill(0,6,$term)];
    }
    private function bind(\mysqli_stmt $statement,string $types,array &$params):void{if($types==='')return;$refs=[];foreach($params as $k=>&$v)$refs[$k]=&$v;$statement->bind_param($types,...$refs);}
}
