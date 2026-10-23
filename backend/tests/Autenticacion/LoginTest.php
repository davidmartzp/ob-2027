<?php

declare(strict_types=1);

namespace Pruebas\Autenticacion;

use App\Compartido\Errores\Validacion;
use App\Modulos\Autenticacion\ServicioUsuarios;
use App\Modulos\Autenticacion\Usuario;
use PHPUnit\Framework\Attributes\Test;
use Pruebas\CasoFuncional;

final class LoginTest extends CasoFuncional
{
    private const string CLAVE = 'origami-bogota-2027';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->getContainer()->get(ServicioUsuarios::class)->crear('equipo@origamibogota.com', 'Equipo OB', self::CLAVE);
    }

    private function login(string $email = 'equipo@origamibogota.com', string $clave = self::CLAVE, string $ip = '203.0.113.10'): \Psr\Http\Message\ResponseInterface
    {
        return $this->pedir('POST', '/auth/login', ['email' => $email, 'password' => $clave], ip: $ip);
    }

    #[Test]
    public function credenciales_correctas_entregan_token_y_usuario_sin_contrasena(): void
    {
        $respuesta = $this->login();

        self::assertSame(200, $respuesta->getStatusCode());
        $cuerpo = $this->json($respuesta);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $cuerpo['access_token']);
        self::assertSame('Bearer', $cuerpo['token_type']);
        self::assertSame(['id', 'name', 'email'], array_keys($cuerpo['user']));
        self::assertStringNotContainsString('password', (string) $respuesta->getBody());
    }

    #[Test]
    public function el_token_no_se_guarda_en_claro(): void
    {
        $token = $this->json($this->login())['access_token'];

        $fila = $this->bd()->table('tokens_acceso')->first();
        self::assertNotSame($token, $fila->hash);
        self::assertSame(hash('sha256', $token), $fila->hash);
    }

    #[Test]
    public function correo_inexistente_y_clave_incorrecta_responden_igual(): void
    {
        $malaClave = $this->login(clave: 'no-es-la-clave');
        $sinCorreo = $this->login(email: 'nadie@ejemplo.com');

        self::assertSame(401, $malaClave->getStatusCode());
        self::assertSame(401, $sinCorreo->getStatusCode());
        self::assertSame((string) $malaClave->getBody(), (string) $sinCorreo->getBody());
    }

    #[Test]
    public function hash_heredado_de_laravel_sigue_valiendo_y_se_actualiza(): void
    {
        $this->bd()->table('users')->insert([
            'name' => 'Usuario heredado',
            'email' => 'heredado@origamibogota.com',
            'password' => password_hash('clave-de-siempre', PASSWORD_BCRYPT, ['cost' => 10]),
        ]);

        self::assertSame(200, $this->login('heredado@origamibogota.com', 'clave-de-siempre')->getStatusCode());
        $hash = Usuario::query()->where('email', 'heredado@origamibogota.com')->value('password');
        self::assertStringStartsWith('$argon2id$', $hash);
        self::assertSame(200, $this->login('heredado@origamibogota.com', 'clave-de-siempre')->getStatusCode());
    }

    #[Test]
    public function sexto_intento_fallido_por_correo_es_429_aunque_la_clave_sea_correcta(): void
    {
        foreach (range(1, 5) as $i) {
            // Cada intento desde una IP distinta: lo que se limita aquí es el correo.
            self::assertSame(401, $this->login(clave: 'mala', ip: "198.51.100.{$i}")->getStatusCode());
        }

        $respuesta = $this->login(ip: '198.51.100.99');
        self::assertSame(429, $respuesta->getStatusCode());
        self::assertSame('demasiados_intentos', $this->json($respuesta)['error']['codigo']);
    }

    #[Test]
    public function limite_por_ip_aunque_cambie_el_correo(): void
    {
        foreach (range(1, 5) as $i) {
            $this->login(email: "otro{$i}@ejemplo.com", clave: 'mala', ip: '192.0.2.7');
        }

        self::assertSame(429, $this->login(ip: '192.0.2.7')->getStatusCode());
        self::assertSame(200, $this->login(ip: '192.0.2.8')->getStatusCode());
    }

    #[Test]
    public function la_ventana_de_15_minutos_se_libera(): void
    {
        foreach (range(1, 5) as $i) {
            $this->login(clave: 'mala', ip: "198.51.100.{$i}");
        }
        $this->reloj()->fijar('now');
        $this->reloj()->avanzar('+16 minutes');

        self::assertSame(200, $this->login()->getStatusCode());
    }

    #[Test]
    public function logout_revoca_el_token(): void
    {
        $token = $this->json($this->login())['access_token'];
        self::assertSame(200, $this->pedir('GET', '/auth/yo', encabezados: self::bearer($token))->getStatusCode());

        self::assertSame(204, $this->pedir('POST', '/auth/logout', encabezados: self::bearer($token))->getStatusCode());
        self::assertSame(401, $this->pedir('GET', '/auth/yo', encabezados: self::bearer($token))->getStatusCode());
    }

    #[Test]
    public function yo_devuelve_el_usuario_de_la_sesion(): void
    {
        $token = $this->json($this->login())['access_token'];

        $cuerpo = $this->json($this->pedir('GET', '/auth/yo', encabezados: self::bearer($token)));
        self::assertSame('equipo@origamibogota.com', $cuerpo['email']);
    }

    #[Test]
    public function cambiar_la_clave_cierra_las_sesiones_abiertas(): void
    {
        $token = $this->json($this->login())['access_token'];

        $this->app->getContainer()->get(ServicioUsuarios::class)->cambiarClave('equipo@origamibogota.com', 'una-clave-nueva-larga');

        self::assertSame(401, $this->pedir('GET', '/auth/yo', encabezados: self::bearer($token))->getStatusCode());
        self::assertSame(200, $this->login(clave: 'una-clave-nueva-larga')->getStatusCode());
    }

    #[Test]
    public function la_api_no_registra_usuarios_y_la_clave_corta_se_rechaza(): void
    {
        self::assertSame(404, $this->pedir('POST', '/register', ['email' => 'x@y.co', 'password' => '123456'])->getStatusCode());

        $this->expectException(Validacion::class);
        $this->app->getContainer()->get(ServicioUsuarios::class)->crear('nuevo@ejemplo.com', 'Nuevo', 'corta');
    }
}
