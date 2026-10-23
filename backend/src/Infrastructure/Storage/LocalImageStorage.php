<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Port\ImageStorage;
use App\Domain\Shared\Exception\ValidationException;
use finfo;
use RuntimeException;

/**
 * Images in `storage/images/` (outside `public/`, so Apache never runs them) served by
 * GET /images/{file}. The type is taken from the content, never from the client's
 * name or MIME, and files carrying PHP code are refused (the legacy upload let anyone
 * store and run a .php).
 */
final readonly class LocalImageStorage implements ImageStorage
{
    public const int MAX_BYTES = 5 * 1024 * 1024;
    /** @var array<string, string> detected MIME → extension */
    private const array TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    /** File names this storage generates or imports from legacy (taller_12.jpg, partner_3.png). */
    public const string NAME_PATTERN = '/^[a-z0-9][a-z0-9_-]{0,80}\.(jpe?g|png|webp)$/i';

    public function __construct(private string $directory, private string $publicBaseUrl)
    {
    }

    public function store(string $bytes, string $prefix, string $field = 'image'): string
    {
        $extension = $this->assertImage($bytes, $field);
        $file = strtolower(preg_replace('/[^a-z0-9-]/i', '', $prefix)) . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $this->write($file, $bytes);

        return $file;
    }

    /**
     * Stores a legacy image under its original name (the database already references it),
     * after the same content checks as an upload.
     *
     * @throws ValidationException when the file is not an acceptable image.
     */
    public function importAs(string $name, string $bytes): void
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            throw ValidationException::field('image', "Nombre de imagen no admitido: {$name}");
        }
        $this->assertImage($bytes, 'image');
        $this->write($name, $bytes);
    }

    /** @return string The extension for the detected type. */
    private function assertImage(string $bytes, string $field): string
    {
        $invalid = static fn (string $message) => ValidationException::field($field, $message);
        if ($bytes === '') {
            throw $invalid('Adjunta una imagen.');
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            throw $invalid('La imagen pesa más de 5 MB.');
        }
        $extension = self::TYPES[self::detectMime($bytes)] ?? null;
        if ($extension === null || @getimagesizefromstring($bytes) === false) {
            throw $invalid('Solo se aceptan imágenes JPEG, PNG o WebP.');
        }
        if (preg_match('/<\?(php|=)|<script\b/i', $bytes) === 1) {
            throw $invalid('La imagen contiene código y no se acepta.');
        }

        return $extension;
    }

    private function write(string $file, string $bytes): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException("Cannot create {$this->directory}");
        }
        if (file_put_contents($this->directory . '/' . $file, $bytes, LOCK_EX) === false) {
            throw new RuntimeException("Cannot write image {$file}");
        }
    }

    public static function detectMime(string $bytes): string
    {
        return (string) (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    }

    public function delete(?string $file): void
    {
        $path = $this->path($file);
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    public function url(?string $file): ?string
    {
        return $file === null || $file === '' ? null : rtrim($this->publicBaseUrl, '/') . '/images/' . rawurlencode($file);
    }

    /** Absolute path of a stored file, or null if the name is not one this storage would use. */
    public function path(?string $file): ?string
    {
        return $file !== null && preg_match(self::NAME_PATTERN, $file) === 1 ? $this->directory . '/' . $file : null;
    }

    /** Content type from the file's content (legacy names may carry the wrong extension). */
    public static function mimeOf(string $path): string
    {
        $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);

        return isset(self::TYPES[$mime]) ? $mime : 'application/octet-stream';
    }
}
