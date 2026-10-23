<?php

declare(strict_types=1);

namespace Tests\Integration\Legacy;

use App\Infrastructure\Persistence\Legacy\LegacyImport;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Attendees;
use Tests\Support\FunctionalTestCase;
use Tests\Support\Schedule;
use Tests\Support\Workshops;

final class LegacyImportTest extends FunctionalTestCase
{
    private string $legacyDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->legacyDir = sys_get_temp_dir() . '/ob-legacy-' . bin2hex(random_bytes(4));
        mkdir($this->legacyDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->legacyDir . '/*') ?: []);
        rmdir($this->legacyDir);
        parent::tearDown();
    }

    #[Test]
    public function the_migrated_schema_has_everything_the_backend_needs(): void
    {
        self::assertSame(['missing' => [], 'extra' => []], $this->service(LegacyImport::class)->checkSchema());
    }

    #[Test]
    public function a_missing_column_is_reported(): void
    {
        $this->db()->getSchemaBuilder()->table('workshops', static fn ($t) => $t->dropColumn('tickets'));

        self::assertSame(['workshops' => ['tickets']], $this->service(LegacyImport::class)->checkSchema()['missing']);
    }

    #[Test]
    public function broken_references_are_counted(): void
    {
        $room = Schedule::room($this->db());
        Schedule::event($this->db(), $room, 999);
        $this->db()->table('events_attendees')->insert(['id_attendee' => 1, 'id_event' => 12345]);

        $broken = $this->service(LegacyImport::class)->brokenReferences();

        self::assertSame(1, $broken['eventos con taller inexistente']);
        self::assertSame(1, $broken['inscripciones a eventos inexistentes']);
    }

    #[Test]
    public function referenced_images_are_copied_checked_and_reported(): void
    {
        $author = Attendees::create($this->db());
        Workshops::create($this->db(), $author, ['image' => 'taller_1.JPG']);
        Workshops::create($this->db(), $author, ['image' => 'taller_2.jpg']);
        Workshops::create($this->db(), $author, ['image' => 'taller_3.png']);
        $this->db()->table('partners')->insert(['name' => 'P', 'whatsapp' => '573001234567', 'message' => 'Hola', 'logo' => 'partner_1.png', 'status' => 1]);
        file_put_contents("{$this->legacyDir}/taller_1.JPG", Workshops::png());
        file_put_contents("{$this->legacyDir}/taller_2.jpg", Workshops::png() . '<?php system($_GET["c"]); ?>');
        file_put_contents("{$this->legacyDir}/partner_1.png", Workshops::png());
        file_put_contents("{$this->legacyDir}/not_referenced.png", Workshops::png());

        $report = $this->service(LegacyImport::class)->importImages($this->legacyDir);

        self::assertSame(['partner_1.png', 'taller_1.JPG'], $report['copied']);
        self::assertSame(['taller_3.png'], $report['missing']);
        self::assertSame(['taller_2.jpg'], array_keys($report['rejected']), 'An image carrying PHP is not copied');
        self::assertFileDoesNotExist("{$this->imageDir}/taller_2.jpg");
        self::assertFileDoesNotExist("{$this->imageDir}/not_referenced.png");

        $served = $this->request('GET', '/images/taller_1.JPG');
        self::assertSame(200, $served->getStatusCode());
        self::assertSame('image/png', $served->getHeaderLine('Content-Type'), 'Served by its real content, whatever the extension says');

        $again = $this->service(LegacyImport::class)->importImages($this->legacyDir);
        self::assertSame(['partner_1.png', 'taller_1.JPG'], $again['existing'], 'Running it again copies nothing new');
    }
}
