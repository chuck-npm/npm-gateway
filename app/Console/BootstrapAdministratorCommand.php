<?php
declare(strict_types=1);
namespace NpmGateway\Console;
use NpmGateway\Exceptions\Domain\AdministratorAlreadyInitializedException;
use NpmGateway\Exceptions\Domain\CredentialNotificationException;
use NpmGateway\Exceptions\Domain\InitializationDatabaseException;
use NpmGateway\Exceptions\Domain\InitializationLockException;
use NpmGateway\Exceptions\Domain\UnsafeBootstrapEnvironmentException;
use NpmGateway\Contracts\SystemInitializationInterface;
use NpmGateway\ValueObjects\InitializeAdministratorRequest;
final class BootstrapAdministratorCommand
{
    public const SUCCESS = 0;
    public const FAILURE = 1;
    public const ALREADY_INITIALIZED = 2;
    public const DATABASE_FAILURE = 3;
    public const NOTIFICATION_FAILURE = 4;
    public const LOCK_UNAVAILABLE = 5;
    public const UNSAFE_ENVIRONMENT = 6;
    public function __construct(
        private readonly SystemInitializationInterface $service,
        private readonly ConsoleIoInterface $io
    ) {}
    /** @param list<string> $arguments */
    public function run(array $arguments): int
    {
        try {
            $options = $this->parse($arguments);
            $interactive = $options === [];
            $this->io->write("NPM Gateway Administrator Bootstrap\n====================================\n\nThis command creates the first corporate administrator.\nIt can run only once.\n\n");
            if ($interactive && !$this->confirm('Continue? [y/N]: ')) { $this->io->write("Cancelled.\n"); return self::FAILURE; }
            $values = [];
            foreach ([
                'employee-number' => 'Employee number: ', 'first-name' => 'First name: ',
                'last-name' => 'Last name: ', 'job-title' => 'Job title: ',
                'business-email' => 'Business email: ', 'personal-email' => 'Personal email (optional): ',
                'company-phone' => 'Company phone (optional): ', 'personal-phone' => 'Personal phone (optional): ',
                'username' => 'Gateway username: ',
            ] as $key => $prompt) {
                $values[$key] = array_key_exists($key, $options) ? $options[$key] : $this->io->read($prompt);
            }
            $username = strtolower(trim((string) $values['username']));
            $this->io->write(sprintf(
                "\nEmployee: %s %s\nEmployee number: %s\nClass: Corporate\nJob title: %s\nBusiness email: %s\nGateway username: %s\n\n",
                trim((string) $values['first-name']), trim((string) $values['last-name']),
                strtoupper(trim((string) $values['employee-number'])), trim((string) $values['job-title']),
                trim((string) $values['business-email']), $username
            ));
            if (!$interactive && !isset($options['yes'])) { throw new UnsafeBootstrapEnvironmentException('Non-interactive bootstrap requires --yes.'); }
            if ($interactive && !$this->confirm('Create this administrator? [y/N]: ')) { $this->io->write("Cancelled.\n"); return self::FAILURE; }
            $skip = isset($options['no-notification']);
            if ($skip && !isset($options['acknowledge-no-notification'])) {
                throw new UnsafeBootstrapEnvironmentException('--no-notification requires --acknowledge-no-notification.');
            }
            $result = $this->service->initialize(new InitializeAdministratorRequest(
                (string) $values['employee-number'], (string) $values['first-name'], (string) $values['last-name'],
                (string) $values['job-title'], (string) $values['business-email'],
                $this->nullable($values['personal-email']), $this->nullable($values['company-phone']),
                $this->nullable($values['personal-phone']), $username, $skip
            ));
            $this->io->write(sprintf(
                "\nAdministrator created successfully.\n\nEmployee public ID: %s\nUser public ID: %s\nUsername: %s\n\nOne-time generated password:\n%s\n\nCredential notice: %s\n\nStore the password securely now.\nGateway does not retain a recoverable copy.\n",
                $result->employeePublicId, $result->userPublicId, $result->username,
                $result->generatedPassword(), $result->credentialNotificationStatus
            ));
            return $result->credentialNotificationStatus === 'failed' ? self::NOTIFICATION_FAILURE : self::SUCCESS;
        } catch (AdministratorAlreadyInitializedException $exception) {
            $this->io->error($exception->getMessage() . "\n"); return self::ALREADY_INITIALIZED;
        } catch (InitializationLockException $exception) {
            $this->io->error("Initialization lock is unavailable.\n"); return self::LOCK_UNAVAILABLE;
        } catch (UnsafeBootstrapEnvironmentException $exception) {
            $this->io->error($exception->getMessage() . "\n"); return self::UNSAFE_ENVIRONMENT;
        } catch (InitializationDatabaseException $exception) {
            $this->io->error("Administrator initialization failed during a database operation.\n"); return self::DATABASE_FAILURE;
        } catch (CredentialNotificationException $exception) {
            $this->io->error("Credential notification configuration is unavailable.\n"); return self::FAILURE;
        } catch (\Throwable $exception) {
            $this->io->error($exception->getMessage() . "\n"); return self::FAILURE;
        }
    }
    /** @param list<string> $arguments @return array<string, string|true> */
    private function parse(array $arguments): array
    {
        $options = [];
        foreach ($arguments as $argument) {
            if ($argument === '--password' || str_starts_with($argument, '--password=')) {
                throw new UnsafeBootstrapEnvironmentException('Password command-line options are prohibited.');
            }
            if (in_array($argument, ['--yes', '--no-notification', '--acknowledge-no-notification'], true)) {
                $options[substr($argument, 2)] = true; continue;
            }
            if (!preg_match('/^--([a-z-]+)=(.*)$/s', $argument, $match)) {
                throw new \InvalidArgumentException("Unknown bootstrap option: {$argument}");
            }
            $options[$match[1]] = $match[2];
        }
        return $options;
    }
    private function confirm(string $prompt): bool { return strtolower(trim($this->io->read($prompt))) === 'y'; }
    private function nullable(mixed $value): ?string { $value = trim((string) $value); return $value === '' ? null : $value; }
}
