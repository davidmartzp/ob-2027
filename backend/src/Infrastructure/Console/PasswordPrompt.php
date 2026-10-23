<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Application\Auth\CreateUser;
use RuntimeException;

/**
 * Reads a password from the keyboard without echo (or from standard input when there is
 * no terminal). Never as an argument, so it does not end up in the server's shell history.
 */
final class PasswordPrompt
{
    public static function ask(): string
    {
        $terminal = function_exists('posix_isatty') && posix_isatty(STDIN);
        $password = self::read('Password (at least ' . CreateUser::MIN_PASSWORD_LENGTH . ' characters): ', $terminal);
        if ($terminal && self::read('Repeat it: ', $terminal) !== $password) {
            throw new RuntimeException('The passwords do not match.');
        }

        return $password;
    }

    private static function read(string $prompt, bool $terminal): string
    {
        if ($terminal) {
            fwrite(STDOUT, $prompt);
            shell_exec('stty -echo');
        }
        $line = rtrim((string) fgets(STDIN), "\r\n");
        if ($terminal) {
            shell_exec('stty echo');
            fwrite(STDOUT, "\n");
        }

        return $line;
    }
}
