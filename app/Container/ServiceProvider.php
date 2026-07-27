<?php
declare(strict_types=1);
namespace NpmGateway\Container;
use DateTimeZone;
use mysqli;
use NpmGateway\Contracts\AuditStoreInterface;
use NpmGateway\Contracts\ClockInterface;
use NpmGateway\Contracts\CredentialNotifierInterface;
use NpmGateway\Contracts\EmployeeStoreInterface;
use NpmGateway\Contracts\PasswordGeneratorInterface;
use NpmGateway\Contracts\UserStoreInterface;
use NpmGateway\Contracts\SystemInitializationInterface;
use NpmGateway\Contracts\InitializationTransactionInterface;
use NpmGateway\Contracts\SessionTokenGeneratorInterface;
use NpmGateway\Contracts\AuthenticationServiceInterface;
use NpmGateway\Contracts\SessionServiceInterface;
use NpmGateway\Configuration\AuthenticationConfig;
use NpmGateway\Database\DatabaseProfiles;
use NpmGateway\Database\MySqlConnectionFactory;
use NpmGateway\Database\MySqlInitializationTransaction;
use NpmGateway\Repositories\SessionRepository;
use NpmGateway\Repositories\LoginAttemptRepository;
use NpmGateway\Security\AuthenticationHasher;
use NpmGateway\Services\SessionService;
use NpmGateway\Services\LoginThrottleService;
use NpmGateway\Services\AuthenticationService;
use NpmGateway\Support\SecureSessionTokenGenerator;
use NpmGateway\Notifications\DisabledCredentialNotifier;
use NpmGateway\Repositories\AuditRepository;
use NpmGateway\Repositories\EmployeeRepository;
use NpmGateway\Repositories\UserRepository;
use NpmGateway\Repositories\DashboardSummaryRepository;
use NpmGateway\Services\DashboardSummaryService;
use NpmGateway\Contracts\DashboardSummaryStoreInterface;
use NpmGateway\Services\AuditService;
use NpmGateway\Services\EmployeeService;
use NpmGateway\Services\NotificationService;
use NpmGateway\Services\PasswordService;
use NpmGateway\Services\SystemInitializationService;
use NpmGateway\Services\UserService;
use NpmGateway\Support\PublicIdGenerator;
use NpmGateway\Support\SecurePasswordGenerator;
use NpmGateway\Support\SystemClock;
final class ServiceProvider
{
    /** @param array<string, mixed> $application */
    public static function build(array $application): Container
    {
        $container = new Container();
        $root = (string) $application['root'];
        $app = (array) $application['config']['app'];
        $notice = require $root . '/config/credential-notification.php';
        /** @var AuthenticationConfig $authentication */
        $authentication = require $root . '/config/authentication.php';
        $container->instance('config.notification', $notice);
        $container->instance(AuthenticationConfig::class, $authentication);
        $container->set(mysqli::class, static fn (): mysqli => MySqlConnectionFactory::connect(DatabaseProfiles::load('application', $root)));
        $container->set(PasswordGeneratorInterface::class, static fn (): PasswordGeneratorInterface => new SecurePasswordGenerator());
        $container->set(ClockInterface::class, static fn (): ClockInterface => new SystemClock(new DateTimeZone((string) ($app['timezone'] ?? 'UTC'))));
        $container->set(CredentialNotifierInterface::class, static fn (): CredentialNotifierInterface => new DisabledCredentialNotifier());
        $container->set(PublicIdGenerator::class, static fn (): PublicIdGenerator => new PublicIdGenerator());
        $container->set(SessionTokenGeneratorInterface::class, static fn (): SessionTokenGeneratorInterface => new SecureSessionTokenGenerator());
        $container->set(AuthenticationHasher::class, static fn(Container $c):AuthenticationHasher=>new AuthenticationHasher($c->get(AuthenticationConfig::class)));
        $container->set(EmployeeStoreInterface::class, static fn (Container $c): EmployeeStoreInterface => new EmployeeRepository($c->get(mysqli::class)));
        $container->set(UserStoreInterface::class, static fn (Container $c): UserStoreInterface => new UserRepository($c->get(mysqli::class)));
        $container->set(AuditStoreInterface::class, static fn (Container $c): AuditStoreInterface => new AuditRepository($c->get(mysqli::class)));
        $container->set(SessionRepository::class,static fn(Container $c):SessionRepository=>new SessionRepository($c->get(mysqli::class)));
        $container->set(LoginAttemptRepository::class,static fn(Container $c):LoginAttemptRepository=>new LoginAttemptRepository($c->get(mysqli::class)));
        $container->set(DashboardSummaryRepository::class,static fn(Container $c):DashboardSummaryRepository=>new DashboardSummaryRepository($c->get(mysqli::class)));
        $container->set(DashboardSummaryStoreInterface::class,static fn(Container $c):DashboardSummaryStoreInterface=>$c->get(DashboardSummaryRepository::class));
        $container->set(DashboardSummaryService::class,static fn(Container $c):DashboardSummaryService=>new DashboardSummaryService($c->get(DashboardSummaryStoreInterface::class)));
        $container->set(InitializationTransactionInterface::class, static fn (Container $c): InitializationTransactionInterface => new MySqlInitializationTransaction($c->get(mysqli::class)));
        $container->set(PasswordService::class, static fn (Container $c): PasswordService => new PasswordService($c->get(PasswordGeneratorInterface::class)));
        $container->set(EmployeeService::class, static fn (Container $c): EmployeeService => new EmployeeService($c->get(EmployeeStoreInterface::class), $c->get(PublicIdGenerator::class)));
        $container->set(UserService::class, static fn (Container $c): UserService => new UserService($c->get(UserStoreInterface::class), $c->get(PublicIdGenerator::class)));
        $container->set(AuditService::class, static fn (Container $c): AuditService => new AuditService($c->get(AuditStoreInterface::class), $c->get(PublicIdGenerator::class)));
        $container->set(NotificationService::class, static fn (Container $c): NotificationService => new NotificationService($c->get(CredentialNotifierInterface::class), $c->get('config.notification')));
        $container->set(SessionService::class,static fn(Container $c):SessionService=>new SessionService($c->get(SessionRepository::class),$c->get(UserStoreInterface::class),$c->get(SessionTokenGeneratorInterface::class),$c->get(AuthenticationHasher::class),$c->get(PublicIdGenerator::class),$c->get(AuthenticationConfig::class),$c->get(AuditService::class)));
        $container->set(LoginThrottleService::class,static fn(Container $c):LoginThrottleService=>new LoginThrottleService($c->get(LoginAttemptRepository::class),$c->get(AuthenticationConfig::class)));
        $container->set(AuthenticationService::class,static fn(Container $c):AuthenticationService=>new AuthenticationService($c->get(InitializationTransactionInterface::class),$c->get(UserStoreInterface::class),$c->get(LoginAttemptRepository::class),$c->get(LoginThrottleService::class),$c->get(SessionService::class),$c->get(AuditService::class),$c->get(AuthenticationHasher::class),$c->get(PublicIdGenerator::class),$c->get(AuthenticationConfig::class)));
        $container->set(AuthenticationServiceInterface::class,static fn(Container $c):AuthenticationServiceInterface=>$c->get(AuthenticationService::class));
        $container->set(SessionServiceInterface::class,static fn(Container $c):SessionServiceInterface=>$c->get(SessionService::class));
        $container->set(SystemInitializationService::class, static fn (Container $c): SystemInitializationService => new SystemInitializationService(
            $c->get(InitializationTransactionInterface::class), $c->get(UserStoreInterface::class), $c->get(EmployeeService::class),
            $c->get(UserService::class), $c->get(PasswordService::class), $c->get(AuditService::class),
            $c->get(NotificationService::class), $c->get(ClockInterface::class), $c->get('config.notification')
        ));
        $container->set(SystemInitializationInterface::class, static fn (Container $c): SystemInitializationInterface => $c->get(SystemInitializationService::class));
        return $container;
    }
}
