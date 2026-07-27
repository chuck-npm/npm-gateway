<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\EmployeeStoreInterface;
use NpmGateway\Exceptions\Domain\DuplicateEmployeeNumberException;
use NpmGateway\Exceptions\Domain\InvalidEmployeeDataException;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\ValueObjects\InitializeAdministratorRequest;
final class EmployeeService
{
    public function __construct(
        private readonly EmployeeStoreInterface $employees,
        private readonly PublicIdGenerator $publicIds
    ) {}
    /** @return array{id: int, public_id: string, employee_number: string, first_name: string, last_name: string, job_title: string, business_email: string, company_phone: ?string} */
    public function createBootstrapCorporate(InitializeAdministratorRequest $request, string $hireDate): array
    {
        $number = strtoupper(trim($request->employeeNumber));
        if (preg_match('/^NPM[0-9]{6}$/', $number) !== 1) {
            throw new InvalidEmployeeDataException('Employee number must use NPM followed by six digits.');
        }
        $first = $this->requiredText($request->firstName, 75, 'First name');
        $last = $this->requiredText($request->lastName, 75, 'Last name');
        $title = $this->requiredText($request->jobTitle, 100, 'Job title');
        $email = trim($request->businessEmail);
        if (strlen($email) > 254 || str_contains($email, "\r") || str_contains($email, "\n") || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmployeeDataException('Business email is invalid.');
        }
        $email = $this->normalizeEmail($email);
        $personalEmail = $request->personalEmail === null || trim($request->personalEmail) === ''
            ? null : $this->normalizeOptionalEmail($request->personalEmail);
        $companyPhone = $this->optionalText($request->companyPhone, 30, 'Company phone');
        $personalPhone = $this->optionalText($request->personalPhone, 30, 'Personal phone');
        if ($this->employees->employeeNumberExists($number)) {
            throw new DuplicateEmployeeNumberException('The employee number is already in use.');
        }
        $publicId = $this->publicIds->generate();
        $id = $this->employees->insert([
            'public_id' => $publicId, 'employee_number' => $number, 'employee_class' => 'corporate',
            'first_name' => $first, 'last_name' => $last, 'business_email' => $email,
            'personal_email' => $personalEmail, 'company_phone' => $companyPhone,
            'personal_phone' => $personalPhone, 'job_title' => $title,
            'employment_status' => 'active', 'hire_date' => $hireDate,
        ]);
        return ['id' => $id, 'public_id' => $publicId, 'employee_number' => $number, 'first_name' => $first, 'last_name' => $last, 'job_title' => $title, 'business_email' => $email, 'company_phone' => $companyPhone];
    }
    private function requiredText(string $value, int $maximum, string $label): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > $maximum || preg_match('/[\\x00-\\x1F\\x7F]/', $value) === 1) {
            throw new InvalidEmployeeDataException("{$label} is invalid.");
        }
        return $value;
    }
    private function optionalText(?string $value, int $maximum, string $label): ?string
    {
        if ($value === null || trim($value) === '') { return null; }
        return $this->requiredText($value, $maximum, $label);
    }
    private function normalizeEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        return $local . '@' . strtolower($domain);
    }
    private function normalizeOptionalEmail(string $email): string
    {
        $email = trim($email);
        if (strlen($email) > 254 || str_contains($email, "\r") || str_contains($email, "\n") || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmployeeDataException('Personal email is invalid.');
        }
        return $this->normalizeEmail($email);
    }
}
