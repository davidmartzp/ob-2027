<?php

declare(strict_types=1);

namespace Tests\Support;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/** Test mailer: records what would be sent, or fails like a mail server that is down. */
final class RecordingMailer implements MailerInterface
{
    /** @var list<Email> */
    public array $sent = [];
    public bool $failing = false;

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        if ($this->failing) {
            throw new TransportException('Connection could not be established with host "smtp.test:465".');
        }
        assert($message instanceof Email);
        $this->sent[] = $message;
    }

    /** @return list<string> */
    public function recipients(): array
    {
        return array_map(static fn (Email $m): string => $m->getTo()[0]->getAddress(), $this->sent);
    }
}
