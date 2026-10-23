<?php

declare(strict_types=1);

/* Commands available in `php bin/console <name>`. */

return [
    App\Infrastructure\Console\MigrateCommand::class,
    App\Infrastructure\Console\RollbackCommand::class,
    App\Infrastructure\Console\SeedCommand::class,
    App\Infrastructure\Console\CreateUserCommand::class,
    App\Infrastructure\Console\ChangePasswordCommand::class,
    App\Infrastructure\Console\SendMailCommand::class,
];
