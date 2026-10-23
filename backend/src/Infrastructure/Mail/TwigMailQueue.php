<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\UniqueConstraintViolationException;
use Psr\Clock\ClockInterface;
use Twig\Environment;

/**
 * Renders the message with Twig (data escaped) and stores it in `email_outbox`. The plain
 * text part comes from `<template>.txt.twig` when it exists, otherwise from the HTML.
 */
final readonly class TwigMailQueue implements MailQueue
{
    public function __construct(
        private Connection $db,
        private Environment $twig,
        private ClockInterface $clock,
        private OutboxProcessor $outbox,
    ) {
    }

    public function queue(EmailMessage $message): ?int
    {
        $now = $this->clock->now()->format('Y-m-d H:i:s');
        $html = $this->twig->render("emails/{$message->template}.html.twig", $message->data);
        $textTemplate = "emails/{$message->template}.txt.twig";
        $text = $this->twig->getLoader()->exists($textTemplate)
            ? $this->twig->render($textTemplate, $message->data)
            : HtmlToText::convert($html);

        try {
            return (int) $this->db->table('email_outbox')->insertGetId([
                'type' => $message->type,
                'id_attendee' => $message->attendeeId,
                'email' => $message->to,
                // A subject never carries line breaks (header injection).
                'subject' => trim((string) preg_replace('/[\r\n]+/', ' ', $message->subject)),
                'html' => $html,
                'text' => $text,
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => $now,
                'unique_key' => $message->uniqueKey,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }
    }

    public function queueAndDeliver(EmailMessage $message): ?int
    {
        $id = $this->queue($message);
        if ($id !== null) {
            $this->outbox->deliver($id);
        }

        return $id;
    }

    public function attendeesWith(string $type): array
    {
        return $this->db->table('email_outbox')
            ->where('type', $type)
            ->whereIn('status', ['pending', 'sending', 'sent'])
            ->whereNotNull('id_attendee')
            ->distinct()
            ->pluck('id_attendee')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();
    }

    public function log(?string $type, int $page, int $perPage): array
    {
        $base = $this->db->table('email_outbox')->when($type !== null, static fn ($q) => $q->where('type', $type));
        $totals = (clone $base)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->map(static fn (mixed $value): int => (int) $value)->all();
        $items = (clone $base)
            ->select(['id', 'type', 'id_attendee', 'email', 'subject', 'status', 'attempts', 'error', 'available_at', 'sent_at', 'created_at'])
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(static fn (object $row): array => (array) $row)
            ->all();

        return [
            'items' => $items,
            'totals' => $totals + ['pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0],
            'page' => $page,
            'per_page' => $perPage,
            'total' => array_sum($totals),
        ];
    }
}
