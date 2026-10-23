<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\CreateUser;
use InvalidArgumentException;

final readonly class CreateUserCommand implements Command
{
    public function __construct(private CreateUser $createUser)
    {
    }

    public function name(): string
    {
        return 'user:create';
    }

    public function description(): string
    {
        return 'Create a CMS user: user:create <email> <name…> (asks for the password)';
    }

    public function run(array $arguments): int
    {
        $email = $arguments[0] ?? '';
        $name = implode(' ', array_slice($arguments, 1));
        if ($email === '' || $name === '') {
            throw new InvalidArgumentException('Usage: php bin/console user:create <email> <name>');
        }

        $user = $this->createUser->execute($email, $name, PasswordPrompt::ask());
        echo "User created: {$user->email} (id {$user->id}).\n";

        return 0;
    }
}
