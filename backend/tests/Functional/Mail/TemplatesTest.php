<?php

declare(strict_types=1);

namespace Tests\Functional\Mail;

use App\Application\Mail\EmailCatalog;
use App\Application\Mail\EmailType;
use App\Application\Mail\EmailVariables;
use App\Application\Port\EmailMessage;
use App\Application\Port\MailQueue;
use App\Domain\Attendee\AttendeeRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;

final class TemplatesTest extends FunctionalTestCase
{
    /** @return iterable<string, array{string}> */
    public static function attendeeTemplates(): iterable
    {
        foreach (EmailCatalog::all() as $type) {
            if (!in_array($type->key, ['access-link', 'code-reminder'], true)) {
                yield $type->key => [$type->key];
            }
        }
    }

    private function render(EmailType $type, array $data): object
    {
        $id = $this->service(MailQueue::class)->queue(new EmailMessage($type->key, 'a@b.co', $type->subject, $type->template, $data));

        return $this->db()->table('email_outbox')->where('id', $id)->first();
    }

    /** Every ported template renders with the legacy variables, leaving nothing unresolved. */
    #[Test]
    #[DataProvider('attendeeTemplates')]
    public function ported_template_renders_without_leftovers(string $key): void
    {
        $id = Attendees::create($this->db(), ['name' => 'Zoé', 'lastname' => 'Peña', 'code' => 27]);
        $attendee = $this->service(AttendeeRepository::class)->findActive($id);

        $email = $this->render(EmailCatalog::get($key), EmailVariables::for($attendee));

        self::assertStringNotContainsString('{{', $email->html);
        self::assertStringNotContainsString('$obj', $email->html);
        self::assertNotSame('', trim((string) $email->text), 'A plain-text part is always generated');
        if (str_contains((string) file_get_contents(dirname(__DIR__, 3) . "/resources/emails/{$key}.html.twig"), '{{ name }}')) {
            self::assertStringContainsString('Zoé', $email->html);
        }
    }

    #[Test]
    public function the_code_email_shows_the_number(): void
    {
        $id = Attendees::create($this->db(), ['name' => 'Zoé', 'code' => 27]);

        $email = $this->render(EmailCatalog::get('code'), EmailVariables::for($this->service(AttendeeRepository::class)->findActive($id)));

        self::assertMatchesRegularExpression('#<div class="code">\s*27\s*</div>#', $email->html);
    }

    #[Test]
    public function the_new_emails_render(): void
    {
        $link = $this->render(EmailCatalog::get('access-link'), ['participants' => [['name' => 'Zoé Peña', 'url' => 'https://x.test/acceso?key=abc']], 'minutes' => 30]);
        $reminder = $this->render(EmailCatalog::get('code-reminder'), ['participants' => [['name' => 'Zoé Peña', 'code' => '27'], ['name' => 'Tomás Peña', 'code' => null]]]);

        self::assertStringContainsString('Entrar como Zoé Peña', $link->html);
        self::assertStringContainsString('header-stripe', $link->html, 'Same frame as the code email');
        self::assertStringContainsString('27', $reminder->html);
        self::assertStringContainsString('Aún no tiene número asignado', $reminder->html);
    }

    #[Test]
    public function user_data_is_escaped_and_subjects_cannot_inject_headers(): void
    {
        $id = Attendees::create($this->db(), ['name' => '<script>alert(1)</script>']);
        $type = EmailCatalog::get('welcome');

        $email = $this->render($type, EmailVariables::for($this->service(AttendeeRepository::class)->findActive($id)));
        $injected = $this->service(MailQueue::class)->queue(new EmailMessage('x', 'a@b.co', "Hola\r\nBcc: x@y.com", 'welcome', ['name' => 'A']));

        self::assertStringNotContainsString('<script>alert(1)', $email->html);
        self::assertStringContainsString('&lt;script&gt;', $email->html);
        self::assertSame('Hola Bcc: x@y.com', $this->db()->table('email_outbox')->where('id', $injected)->value('subject'));
    }
}
