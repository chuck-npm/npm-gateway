<?php
declare(strict_types=1);
namespace NpmGateway\Repositories;
use mysqli;
use NpmGateway\Contracts\EmployeeStoreInterface;
use NpmGateway\Contracts\EmployeeDirectoryStoreInterface;
use NpmGateway\Contracts\HrEmployeeStoreInterface;
use NpmGateway\ValueObjects\EmployeeDirectoryCriteria;
final class EmployeeRepository implements EmployeeStoreInterface,EmployeeDirectoryStoreInterface,HrEmployeeStoreInterface
{
    public function __construct(private readonly mysqli $connection) {}
    public function employeeNumberExists(string $employeeNumber): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM employees WHERE employee_number = ? LIMIT 1');
        $statement->bind_param('s', $employeeNumber);
        $statement->execute();
        $exists = $statement->get_result()->num_rows > 0;
        $statement->close();
        return $exists;
    }
    public function insert(array $employee): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO employees
             (public_id, employee_number, employee_class, first_name, last_name, business_email,
              personal_email, company_phone, personal_phone, job_title, employment_status, start_date, comments, created_by, updated_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $comments=$employee['comments']??null;$createdBy=$employee['created_by']??null;$updatedBy=$employee['updated_by']??null;
        $statement->bind_param(
            'sssssssssssssii',
            $employee['public_id'], $employee['employee_number'], $employee['employee_class'],
            $employee['first_name'], $employee['last_name'], $employee['business_email'],
            $employee['personal_email'], $employee['company_phone'], $employee['personal_phone'],
            $employee['job_title'], $employee['employment_status'], $employee['start_date'], $comments,
            $createdBy,$updatedBy
        );
        $statement->execute();
        $id = $this->connection->insert_id;
        $statement->close();
        return $id;
    }
    public function searchDirectory(EmployeeDirectoryCriteria $criteria):array
    {
        [$where,$types,$params]=$this->directoryWhere($criteria);
        $orders=['employee_number'=>'e.employee_number','name'=>'e.last_name, e.first_name','job_title'=>'e.job_title','employee_class'=>'e.employee_class','status'=>'e.employment_status','primary_property'=>'primary_property_name'];
        $direction=$criteria->direction==='desc'?'DESC':'ASC';$order=$orders[$criteria->sort]??$orders['name'];$offset=($criteria->page-1)*$criteria->perPage;
        $sql="SELECT e.public_id employee_public_id,e.employee_number,CONCAT(e.first_name,' ',e.last_name) display_name,e.job_title,e.employee_class,e.employment_status,e.business_email,e.company_phone,
            COALESCE((SELECT p.display_name FROM employee_property_assignments a JOIN properties p ON p.id=a.property_id WHERE a.employee_id=e.id AND a.ends_on IS NULL AND a.is_primary=1 ORDER BY a.starts_on DESC LIMIT 1),
              CASE WHEN e.employee_class='corporate' THEN 'Corporate' ELSE NULL END,
              CASE WHEN EXISTS(SELECT 1 FROM employee_property_assignments a2 WHERE a2.employee_id=e.id AND a2.ends_on IS NULL) THEN 'Multiple properties' ELSE 'Not assigned' END) primary_property_name,
            CASE WHEN u.id IS NULL THEN 'None' WHEN u.status='active' THEN 'Active' ELSE 'Inactive' END gateway_access_status
            FROM employees e LEFT JOIN users u ON u.employee_id=e.id {$where} ORDER BY {$order} {$direction}, e.employee_number ASC LIMIT ? OFFSET ?";
        $params[]=$criteria->perPage;$params[]=$offset;$types.='ii';$statement=$this->connection->prepare($sql);$this->bind($statement,$types,$params);$statement->execute();$rows=$statement->get_result()->fetch_all(MYSQLI_ASSOC);$statement->close();return $rows;
    }
    public function countDirectoryResults(EmployeeDirectoryCriteria $criteria):int
    {
        [$where,$types,$params]=$this->directoryWhere($criteria);$statement=$this->connection->prepare("SELECT COUNT(*) FROM employees e {$where}");$this->bind($statement,$types,$params);$statement->execute();$count=(int)$statement->get_result()->fetch_row()[0];$statement->close();return $count;
    }
    public function highestEmployeeNumber():?string
    {
        $row=$this->connection->query("SELECT employee_number FROM employees WHERE employee_number REGEXP '^NPM[0-9]{6}$' ORDER BY employee_number DESC LIMIT 1")->fetch_row();return isset($row[0])?(string)$row[0]:null;
    }
    public function eligibleProperties():array
    {
        return $this->connection->query("SELECT id,public_id,prop_id,property_code,display_name FROM properties WHERE status='active' AND NOT(prop_id=1 AND property_code='CO' AND slug='corporate') ORDER BY display_name")->fetch_all(MYSQLI_ASSOC);
    }
    public function findOperationalProperty(int $id):?array
    {
        $s=$this->connection->prepare("SELECT id,public_id,prop_id,property_code,display_name,status,slug FROM properties WHERE id=? LIMIT 1");$s->bind_param('i',$id);$s->execute();$row=$s->get_result()->fetch_assoc();$s->close();return is_array($row)?$row:null;
    }
    public function corporateProperty():?array
    {
        $row=$this->connection->query("SELECT id,public_id,prop_id,property_code,display_name,status,slug FROM properties WHERE prop_id=1 AND property_code='CO' AND slug='corporate' LIMIT 1")->fetch_assoc();return is_array($row)?$row:null;
    }
    public function insertAssignment(array $a):int
    {
        $s=$this->connection->prepare('INSERT INTO employee_property_assignments(public_id,employee_id,property_id,assignment_type,is_primary,starts_on,ends_on,created_by,updated_by) VALUES(?,?,?,?,1,?,NULL,?,?)');$s->bind_param('siissii',$a['public_id'],$a['employee_id'],$a['property_id'],$a['assignment_type'],$a['starts_on'],$a['created_by'],$a['updated_by']);$s->execute();$id=$this->connection->insert_id;$s->close();return $id;
    }
    /** @return array{string,string,list<mixed>} */
    private function directoryWhere(EmployeeDirectoryCriteria $criteria):array
    {
        $clauses=[];$types='';$params=[];
        if($criteria->search!==''){$term='%'.str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$criteria->search).'%';$clauses[]="(e.employee_number LIKE ? ESCAPE '\\\\' OR e.first_name LIKE ? ESCAPE '\\\\' OR e.last_name LIKE ? ESCAPE '\\\\' OR CONCAT(e.first_name,' ',e.last_name) LIKE ? ESCAPE '\\\\' OR e.job_title LIKE ? ESCAPE '\\\\' OR EXISTS(SELECT 1 FROM employee_property_assignments sa JOIN properties sp ON sp.id=sa.property_id WHERE sa.employee_id=e.id AND sa.ends_on IS NULL AND sp.display_name LIKE ? ESCAPE '\\\\'))";for($i=0;$i<6;$i++){$types.='s';$params[]=$term;}}
        if($criteria->employeeClass!=='all'){$clauses[]='e.employee_class=?';$types.='s';$params[]=$criteria->employeeClass;}
        if($criteria->employmentStatus!=='all'){$clauses[]='e.employment_status=?';$types.='s';$params[]=$criteria->employmentStatus;}
        return [$clauses===[]?'':'WHERE '.implode(' AND ',$clauses),$types,$params];
    }
    /** @param list<mixed> $params */
    private function bind(\mysqli_stmt $statement,string $types,array &$params):void
    {
        if($types==='')return;$references=[];foreach($params as $key=>&$value)$references[$key]=&$value;$statement->bind_param($types,...$references);
    }
}
