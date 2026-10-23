<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Workshop\EditWorkshop;
use App\Application\Workshop\InstructorWorkshops;
use App\Application\Workshop\ManageWorkshops;
use App\Domain\Shared\Exception\ValidationException;
use App\Infrastructure\Http\Json;
use App\Infrastructure\Http\RequestSession;
use App\Infrastructure\Storage\LocalImageStorage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final readonly class WorkshopController
{
    public function __construct(
        private InstructorWorkshops $instructor,
        private EditWorkshop $edit,
        private ManageWorkshops $manage,
    ) {
    }

    /** @param array{id: string} $args */
    public function propose(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $created = $this->instructor->propose(RequestSession::of($request), (int) $args['id'], (array) $request->getParsedBody());

        return Json::respond($response, $created, 201);
    }

    /** @param array{id: string} $args */
    public function listMine(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->instructor->list(RequestSession::of($request), (int) $args['id']));
    }

    /** @param array{id: string} $args */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->edit->update(RequestSession::of($request), (int) $args['id'], (array) $request->getParsedBody()));
    }

    /** @param array{id: string} $args */
    public function withdraw(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->edit->withdraw(RequestSession::of($request), (int) $args['id']);

        return Json::noContent($response);
    }

    /** @param array{id: string} $args */
    public function uploadImage(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $result = $this->edit->setImage(RequestSession::of($request), (int) $args['id'], self::uploadedBytes($request, 'image'));

        return Json::respond($response, $result);
    }

    public function publicList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->publicList());
    }

    public function cmsList(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return Json::respond($response, $this->manage->cmsList());
    }

    /** @param array{id: string} $args */
    public function patch(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return Json::respond($response, $this->manage->patch((int) $args['id'], (array) $request->getParsedBody()));
    }

    /** Reads a multipart file field; oversize uploads are refused before reading them. */
    public static function uploadedBytes(ServerRequestInterface $request, string $field): string
    {
        $file = $request->getUploadedFiles()[$field] ?? null;
        // Over post_max_size PHP drops the whole body, so the file never arrives: tell the real reason.
        if ($file === null && (int) $request->getHeaderLine('Content-Length') > LocalImageStorage::MAX_BYTES) {
            throw ValidationException::field($field, 'La imagen pesa más de 5 MB.');
        }
        if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
            throw ValidationException::field($field, 'Adjunta una imagen.');
        }
        if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || ($file->getSize() ?? 0) > LocalImageStorage::MAX_BYTES) {
            throw ValidationException::field($field, 'La imagen pesa más de 5 MB.');
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw ValidationException::field($field, 'No se pudo recibir la imagen. Inténtalo de nuevo.');
        }

        return (string) $file->getStream();
    }
}
