<?php
declare(strict_types=1);
namespace NpmGateway\Services;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Contracts\SystemInitializationInterface;
use NpmGateway\Contracts\UserStoreInterface;
use NpmGateway\Exceptions\Domain\AdministratorAlreadyInitializedException;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\Exceptions\Domain\InitializationDatabaseException;
use NpmGateway\Exceptions\Domain\InitializationLockException;
use NpmGateway\ValueObjects\CredentialNotice;
use NpmGateway\ValueObjects\InitializeAdministratorRequest;
use NpmGateway\ValueObjects\InitializeAdministratorResult;
final class SystemInitializationService implements SystemInitializationInterface
{
    private const LOCK_NAME = 'npm_gateway:bootstrap_administrator';
    public function __construct(
        private readonly InitializationTransactionInterface $transaction,
        private readonly UserStoreInterface $users,
        private readonly EmployeeService $employeeService,
        private readonly UserService $userService,
        private readonly PasswordService $passwordService,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
        private readonly ClockInterface $clock,
        private readonly array $notificationConfig
    ) {}
    public function initialize(InitializeAdministratorRequest $request): InitializeAdministratorResult
    {
        $this->notificationService->validateMode($request->skipNotification);
        if ($this->users->anyExists()) {
            throw new AdministratorAlreadyInitializedException('Gateway has already been initialized. Use the future user-administration workflow to create additional accounts.');
        }
        if (!$this->acquireLock()) { throw new InitializationLockException('The initialization lock is unavailable.'); }
        $credential = null;
        try {
            $this->transaction->begin();
            try {
                if ($this->users->anyExists()) {
                    throw new AdministratorAlreadyInitializedException('Gateway has already been initialized. Use the future user-administration workflow to create additional accounts.');
                }
                $now = $this->clock->now();
                $timestamp = $now->format('Y-m-d H:i:s');
                $credential = $this->passwordService->generate();
                $employee = $this->employeeService->createBootstrapCorporate($request, $now->format('Y-m-d'));
                $user = $this->userService->createBootstrapUser((int) $employee['id'], $request->username, $credential->passwordHash, $timestamp);
                $this->auditService->record(
                    'system.administrator_initialized', (int) $user['id'], (int) $employee['id'],
                    (string) $user['public_id'], 'Initial corporate administrator created.',
                    [
                        'employee_public_id' => $employee['public_id'], 'user_public_id' => $user['public_id'],
                        'employee_number' => $employee['employee_number'], 'username' => $user['username'],
                        'employee_class' => 'corporate', 'job_title' => $employee['job_title'],
                        'initialized_at' => $timestamp, 'notification_status' => 'pending',
                    ],
                    $timestamp
                );
                $this->transaction->commit();
            } catch (\Throwable $exception) {
                $this->transaction->rollback();
                if ($exception instanceof AdministratorAlreadyInitializedException || $exception instanceof \InvalidArgumentException || $exception instanceof \RuntimeException && str_starts_with($exception::class, 'NpmGateway\\Exceptions\\Domain\\')) {
                    throw $exception;
                }
                throw new InitializationDatabaseException('Administrator initialization database operation failed.', 0, $exception);
            }

            $status = 'failed';
            $error = null;
            try {
                $notice = new CredentialNotice(
                    (string) ($this->notificationConfig['recipient_email'] ?? ''),
                    (string) ($this->notificationConfig['recipient_name'] ?? ''),
                    (string) ($this->notificationConfig['subject'] ?? ''),
                    $employee['first_name'] . ' ' . $employee['last_name'],
                    $employee['employee_number'], $user['username'], $credential->plaintextPassword(),
                    $employee['job_title'], $employee['company_phone'], $timestamp
                );
                $status = $this->notificationService->send($notice, $request->skipNotification);
            } catch (CredentialNotificationException $exception) {
                $error = 'Credential notification transport failed.';
            }
            $event = $status === 'sent'
                ? 'system.administrator_credential_notice_sent'
                : ($status === 'skipped-local' ? 'system.administrator_credential_notice_skipped' : 'system.administrator_credential_notice_failed');
            $this->auditService->record(
                $event, (int) $user['id'], (int) $employee['id'], (string) $user['public_id'],
                'Administrator credential notice result recorded.',
                ['notification_status' => $status, 'recorded_at' => $timestamp],
                $timestamp
            );
            return new InitializeAdministratorResult(
                $employee['public_id'], $user['public_id'], $employee['employee_number'], $user['username'],
                $credential->plaintextPassword(), $status, $error, $timestamp
            );
        } finally {
            $this->releaseLock();
        }
    }
    private function acquireLock(): bool
    {
        return $this->transaction->acquire(self::LOCK_NAME, 5);
    }
    private function releaseLock(): void
    {
        $this->transaction->release(self::LOCK_NAME);
    }
}
