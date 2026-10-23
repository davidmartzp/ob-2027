<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Domain\Shared\Exception\ValidationException;

/** Uploaded images (workshop photos, partner logos), stored where no code can run. */
interface ImageStorage
{
    /**
     * Checks the real content (JPEG, PNG or WebP only, no embedded code, size limit) and
     * stores it under a server-generated name.
     *
     * @return string The stored file name.
     * @throws ValidationException on field `$field` when the file is not an acceptable image.
     */
    public function store(string $bytes, string $prefix, string $field = 'image'): string;

    public function delete(?string $file): void;

    /** Absolute public URL of a stored file, or null. */
    public function url(?string $file): ?string;
}
