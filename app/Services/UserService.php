<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\UserStoreInterface;
use NpmGateway\Exceptions\Domain\InvalidUsernameException;
use NpmGateway\Exceptions\Domain\UsernameAlreadyExistsException;
use NpmGateway\Support\PublicIdGenerator;
final class UserService
{
    public function __construct(
        private readonly UserStoreInterface $users,
        private readonly PublicIdGenerator $publicIds
    ) {}
    public function normalizeUsername(string $username): string { return strtolower(trim($username)); }
    /** @return array{id: int, public_id: string, username: string} */
    public function createBootstrapUser(int $employeeId, string $username, string $passwordHash, string $changedAt): array
    {
        $username = $this->normalizeUsername($username);
        if (preg_match('/^[a-z][a-z0-9]{1,49}$/', $username) !== 1) {
            throw new InvalidUsernameException('Username must begin with a lowercase letter and contain only lowercase letters and digits.');
        }
        if ($this->users->usernameExists($username)) {
            throw new UsernameAlreadyExistsException('The username is already in use.');
        }
        $publicId = $this->publicIds->generate();
        $id = $this->users->insert([
            'public_id' => $publicId, 'employee_id' => $employeeId, 'username' => $username,
            'password_hash' => $passwordHash, 'status' => 'active', 'password_changed_at' => $changedAt,
        ]);
        return ['id' => $id, 'public_id' => $publicId, 'username' => $username];
    }
}
