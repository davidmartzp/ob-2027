<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Keeps the hexagonal dependency rule: Domain ← Application ← Infrastructure.
 * The core (Domain, Application) never imports frameworks or adapters.
 */
final class ArchitectureTest extends TestCase
{
    /** @return iterable<string, array{string, list<string>}> */
    public static function layers(): iterable
    {
        $frameworks = ['Slim\\', 'Illuminate\\', 'Twig\\', 'DI\\', 'Psr\\Http\\', 'Symfony\\', 'Dotenv\\'];

        yield 'Domain depends on nothing outside itself' => ['Domain', [...$frameworks, 'App\\Application\\', 'App\\Infrastructure\\']];
        yield 'Application does not depend on adapters' => ['Application', [...$frameworks, 'App\\Infrastructure\\']];
    }

    /** @param list<string> $forbidden */
    #[Test]
    #[DataProvider('layers')]
    public function layer_respects_the_dependency_rule(string $layer, array $forbidden): void
    {
        $violations = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2) . "/src/{$layer}"));
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            preg_match_all('/^use\s+([^;\s]+)/m', (string) file_get_contents($file->getPathname()), $imports);
            foreach ($imports[1] as $import) {
                foreach ($forbidden as $prefix) {
                    if (str_starts_with($import, $prefix)) {
                        $violations[] = $file->getFilename() . ' → ' . $import;
                    }
                }
            }
        }

        self::assertSame([], $violations);
    }
}
