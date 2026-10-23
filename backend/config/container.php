<?php

declare(strict_types=1);

/*
 * Container definitions: which adapter implements each port, plus what autowiring cannot
 * guess. Controllers and use cases are autowired and need no entry here.
 */

use App\Application\Auth\AuthSettings;
use App\Application\Mail\TeamMailbox;
use App\Application\Port\ImageStorage;
use App\Application\Port\MailQueue;
use App\Application\Port\PasswordHasher;
use App\Application\Port\RateLimiter;
use App\Application\Port\TransactionManager;
use App\Domain\Attendee\AttendeeRepository;
use App\Domain\Attendee\CodePolicy;
use App\Domain\Attendee\TurnSettings;
use App\Domain\Auth\AccessTokenRepository;
use App\Domain\Auth\UserRepository;
use App\Domain\Partner\PartnerRepository;
use App\Domain\Schedule\ScheduleRepository;
use App\Domain\Workshop\WorkshopOptions;
use App\Domain\Workshop\WorkshopRepository;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Config\Settings;
use App\Infrastructure\Mail\MailSettings;
use App\Infrastructure\Mail\TransportFactory;
use App\Infrastructure\Mail\TwigMailQueue;
use App\Infrastructure\Persistence\DbRateLimiter;
use App\Infrastructure\Persistence\DbTransactionManager;
use App\Infrastructure\Persistence\Repository\DbScheduleRepository;
use App\Infrastructure\Persistence\Migration\Migrator;
use App\Infrastructure\Persistence\Migration\Seeder;
use App\Infrastructure\Persistence\Repository\DbAccessTokenRepository;
use App\Infrastructure\Persistence\Repository\EloquentAttendeeRepository;
use App\Infrastructure\Persistence\Repository\EloquentPartnerRepository;
use App\Infrastructure\Persistence\Repository\EloquentWorkshopRepository;
use App\Infrastructure\Persistence\Repository\EloquentUserRepository;
use App\Infrastructure\Security\NativePasswordHasher;
use App\Infrastructure\Storage\LocalImageStorage;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

use function DI\autowire;
use function DI\get;

return static fn (Settings $settings, string $root): array => [
    Settings::class => $settings,
    ResponseFactoryInterface::class => get(ResponseFactory::class),
    ClockInterface::class => get(SystemClock::class),

    // Ports → adapters
    UserRepository::class => autowire(EloquentUserRepository::class),
    AttendeeRepository::class => autowire(EloquentAttendeeRepository::class),
    AccessTokenRepository::class => autowire(DbAccessTokenRepository::class),
    RateLimiter::class => autowire(DbRateLimiter::class),
    MailQueue::class => autowire(TwigMailQueue::class),
    PasswordHasher::class => autowire(NativePasswordHasher::class),
    WorkshopRepository::class => autowire(EloquentWorkshopRepository::class),
    PartnerRepository::class => autowire(EloquentPartnerRepository::class),
    ScheduleRepository::class => autowire(DbScheduleRepository::class),
    TransactionManager::class => autowire(DbTransactionManager::class),
    ImageStorage::class => get(LocalImageStorage::class),
    // Images live outside public/ and are served by GET /images/{file}; APP_URL must include APP_BASE_PATH.
    LocalImageStorage::class => static fn (): LocalImageStorage => new LocalImageStorage(
        $root . '/storage/images',
        $settings->string('APP_URL', 'http://localhost:8080'),
    ),

    AuthSettings::class => static fn (): AuthSettings => new AuthSettings($settings->int('CMS_TOKEN_HOURS', 12)),

    // Congress rules
    CodePolicy::class => static fn (): CodePolicy => new CodePolicy(
        minAge: $settings->int('CODE_MIN_AGE', 12),
        excludedPaymentMethods: $settings->list('CODE_EXCLUDED_PAYMENT_METHODS') ?: ['Grupo de apoyo', 'Invitado', 'Invitado(a)'],
    ),
    TurnSettings::class => static fn (): TurnSettings => new TurnSettings(
        saturday: $settings->string('EVENT_SATURDAY', '2027-06-05'),
        sunday: $settings->string('EVENT_SUNDAY', '2027-06-06'),
        startTime: $settings->string('TURN_START_TIME', '08:00'),
        endTime: $settings->string('TURN_END_TIME', '19:00'),
        groupSize: $settings->int('TURN_GROUP_SIZE', 25),
        minutesPerGroup: $settings->int('TURN_MINUTES', 10),
        // Rehearsal mode never applies in production, whatever .env says.
        bypass: !$settings->isProduction() && $settings->bool('TURN_BYPASS'),
    ),
    WorkshopOptions::class => static fn (): WorkshopOptions => new WorkshopOptions(
        levels: $settings->list('WORKSHOP_LEVELS') ?: ['P', 'I', 'A'],
        audiences: $settings->list('WORKSHOP_AUDIENCES') ?: ['A', 'N'],
    ),
    TeamMailbox::class => static fn (): TeamMailbox => new TeamMailbox($settings->string('MAIL_TEAM_ADDRESS', 'inscripciones@origamibogota.com')),

    // Database
    Capsule::class => static function () use ($settings): Capsule {
        $capsule = new Capsule();
        $driver = $settings->string('DB_CONNECTION', 'mariadb');
        $capsule->addConnection($driver === 'sqlite'
            ? ['driver' => 'sqlite', 'database' => $settings->string('DB_DATABASE'), 'foreign_key_constraints' => true]
            : [
                'driver' => $driver,
                'host' => $settings->string('DB_HOST'),
                'port' => $settings->int('DB_PORT', 3306),
                'database' => $settings->string('DB_DATABASE'),
                'username' => $settings->string('DB_USERNAME'),
                'password' => $settings->string('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'strict' => true,
            ]);

        return $capsule;
    },
    Connection::class => static fn (ContainerInterface $c): Connection => $c->get(Capsule::class)->getConnection(),
    Migrator::class => static fn (ContainerInterface $c): Migrator => new Migrator($c->get(Connection::class), $root . '/database/migrations'),
    Seeder::class => static fn (ContainerInterface $c): Seeder => new Seeder($c->get(Connection::class), $root . '/database/seeds'),

    // Email: Symfony Mailer over MAILER_DSN (production refuses anything without TLS).
    MailerInterface::class => static fn (): MailerInterface => new Mailer(
        TransportFactory::create($settings->string('MAILER_DSN', 'null://null'), $settings->isProduction()),
    ),
    MailSettings::class => static fn (): MailSettings => new MailSettings(
        fromAddress: $settings->string('MAIL_FROM_ADDRESS', 'inscripciones@origamibogota.com'),
        fromName: $settings->string('MAIL_FROM_NAME', 'Origami Bogotá'),
        sendPerMinute: $settings->int('MAIL_SEND_PER_MINUTE', 30),
        sendIntervalMs: $settings->int('MAIL_SEND_INTERVAL_MS', 1000),
    ),

    // Email templates: `.html.twig` is HTML-escaped, `.txt.twig` is not (escaping by file name).
    Environment::class => static fn (): Environment => new Environment(new FilesystemLoader($root . '/resources'), [
        'autoescape' => 'name',
        'strict_variables' => true,
        'cache' => false,
    ]),
];
