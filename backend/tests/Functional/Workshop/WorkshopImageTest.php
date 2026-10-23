<?php

declare(strict_types=1);

namespace Tests\Functional\Workshop;

use App\Application\Auth\TokenService;
use App\Domain\Auth\TokenType;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Workshops;

final class WorkshopImageTest extends FunctionalTestCase
{
    private int $workshop;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $author = Attendees::create($this->db());
        $this->workshop = Workshops::create($this->db(), $author);
        $this->token = $this->service(TokenService::class)->issue(TokenType::Attendee, $author)['token'];
    }

    private function uploadImage(string $bytes, string $name = 'modelo.png'): \Psr\Http\Message\ResponseInterface
    {
        return $this->upload("/workshops/{$this->workshop}/image", 'image', $bytes, $name, self::bearer($this->token));
    }

    private function storedFiles(): array
    {
        return array_map('basename', glob($this->imageDir . '/*') ?: []);
    }

    #[Test]
    public function a_valid_image_is_stored_under_a_generated_name_and_served(): void
    {
        $response = $this->uploadImage(Workshops::png(), '../../shell.php.png');

        self::assertSame(200, $response->getStatusCode());
        $url = $this->json($response)['image'];
        self::assertMatchesRegularExpression('#^http://api\.test/images/workshop-\d+-[a-f0-9]{16}\.png$#', $url);

        $served = $this->request('GET', parse_url($url, PHP_URL_PATH));
        self::assertSame(200, $served->getStatusCode());
        self::assertSame('image/png', $served->getHeaderLine('Content-Type'));
        self::assertSame('nosniff', $served->getHeaderLine('X-Content-Type-Options'));
        self::assertSame(Workshops::png(), (string) $served->getBody());
    }

    #[Test]
    public function a_new_image_replaces_the_old_one(): void
    {
        $this->uploadImage(Workshops::png());
        $first = $this->storedFiles();
        $this->uploadImage(Workshops::png());

        self::assertCount(1, $this->storedFiles());
        self::assertNotSame($first, $this->storedFiles());
    }

    #[Test]
    public function a_php_file_is_refused(): void
    {
        $response = $this->uploadImage('<?php system($_GET["c"]); ?>', 'foto.jpg');

        self::assertSame(422, $response->getStatusCode());
        self::assertArrayHasKey('image', $this->json($response)['error']['fields']);
        self::assertSame([], $this->storedFiles());
    }

    #[Test]
    public function a_real_image_with_php_inside_is_refused(): void
    {
        $polyglot = Workshops::png() . '<?php system($_GET["c"]); ?>';
        self::assertNotFalse(getimagesizefromstring($polyglot), 'It is a valid PNG for image parsers');

        self::assertSame(422, $this->uploadImage($polyglot)->getStatusCode());
        self::assertSame([], $this->storedFiles());
    }

    #[Test]
    public function svg_and_oversize_files_are_refused(): void
    {
        self::assertSame(422, $this->uploadImage('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'logo.svg')->getStatusCode());
        self::assertSame(422, $this->uploadImage(Workshops::png() . str_repeat("\0", 8 * 1024 * 1024), 'grande.png')->getStatusCode());
    }

    #[Test]
    public function uploading_requires_the_author_or_the_cms(): void
    {
        $stranger = $this->service(TokenService::class)->issue(TokenType::Attendee, 999)['token'];

        self::assertSame(401, $this->upload("/workshops/{$this->workshop}/image", 'image', Workshops::png(), 'a.png')->getStatusCode());
        self::assertSame(403, $this->upload("/workshops/{$this->workshop}/image", 'image', Workshops::png(), 'a.png', self::bearer($stranger))->getStatusCode());
        self::assertSame([], $this->storedFiles());
    }

    #[Test]
    public function only_stored_image_names_are_served(): void
    {
        foreach (['/images/..%2F.env', '/images/shell.php', '/images/a.svg', '/images/no-existe.png'] as $path) {
            self::assertSame(404, $this->request('GET', $path)->getStatusCode(), $path);
        }
    }
}
