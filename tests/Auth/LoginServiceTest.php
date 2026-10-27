<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Core\Database;
use App\Modules\Auth\LoginException;
use App\Modules\Auth\LoginService;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\PruebaConBase;

/**
 * El inicio de sesión (LoginService::autenticar): qué cuentas entran, qué mensaje recibe cada error sin revelar qué
 * correos tienen cuenta, el bloqueo por intentos fallidos (por correo y por IP) y el registro de cada intento.
 * Límites de tests/bootstrap.php: 5 fallos por correo, 30 por IP, ventana de 15 minutos, registros por 90 días.
 */
#[TestDox('Inicio de sesión')]
final class LoginServiceTest extends PruebaConBase
{
    private const EMAIL = 'docente@udec.cl';
    private const IP = '10.0.0.1';
    private const ERROR_GENERICO = 'Correo o contraseña incorrectos.';

    private LoginService $login;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login = new LoginService();
    }

    #[TestDox('Con el correo y la contraseña correctos entra, con sus datos de sesión, y queda registrado')]
    public function testCredencialesCorrectas(): void
    {
        $id = $this->crearUsuario(self::EMAIL);

        $resultado = $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP);

        $this->assertSame(
            ['id' => $id, 'nombre' => 'Persona de Prueba', 'email' => self::EMAIL, 'perfil' => 'docente'],
            $resultado['usuario'],
            'La sesión guarda solo id, nombre, correo y perfil (nunca el hash).'
        );
        $this->assertNull($resultado['acceso_anterior'], 'Es su primer ingreso.');
        $this->assertSame(1, (int) Database::fetchValue(
            'SELECT COUNT(*) FROM intentos_login WHERE email = ? AND exitoso = 1 AND usuario_id = ?',
            [self::EMAIL, $id]
        ));
        $this->assertNotNull(Database::fetchValue('SELECT ultimo_acceso FROM usuarios WHERE id = ?', [$id]));

        $segundo = $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP);
        $this->assertNotNull($segundo['acceso_anterior'], 'El segundo ingreso informa cuándo fue el anterior.');
    }

    #[TestDox('Con la contraseña incorrecta no entra y el intento fallido queda registrado con su cuenta')]
    public function testContrasenaIncorrecta(): void
    {
        $id = $this->crearUsuario(self::EMAIL);

        $this->assertRechazado(self::EMAIL, 'otra-clave', self::ERROR_GENERICO);
        $this->assertSame(1, (int) Database::fetchValue(
            'SELECT COUNT(*) FROM intentos_login WHERE email = ? AND exitoso = 0 AND usuario_id = ?',
            [self::EMAIL, $id]
        ));
    }

    #[TestDox('Con un correo sin cuenta recibe el mismo mensaje: no se revela qué correos tienen cuenta')]
    public function testCorreoQueNoExiste(): void
    {
        $this->assertRechazado('nadie@udec.cl', self::CLAVE, self::ERROR_GENERICO);
        $this->assertSame(1, (int) Database::fetchValue(
            'SELECT COUNT(*) FROM intentos_login WHERE email = ? AND exitoso = 0 AND usuario_id IS NULL',
            ['nadie@udec.cl']
        ));
    }

    #[TestDox('Una cuenta que todavía no crea su contraseña no puede entrar')]
    public function testCuentaSinContrasena(): void
    {
        $this->crearUsuario(self::EMAIL, clave: null);

        $this->assertRechazado(self::EMAIL, '', self::ERROR_GENERICO);
        $this->assertRechazado(self::EMAIL, self::CLAVE, self::ERROR_GENERICO);
    }

    #[TestDox('Una cuenta desactivada no entra aunque la contraseña sea correcta, y se le dice por qué')]
    public function testCuentaDesactivada(): void
    {
        $this->crearUsuario(self::EMAIL, activo: false);

        $this->assertRechazado(self::EMAIL, self::CLAVE, 'Tu cuenta está desactivada. Habla con el encargado de bodega.');
    }

    #[TestDox('Una cuenta desactivada con la contraseña incorrecta recibe el mensaje general: no se revela que existe')]
    public function testCuentaDesactivadaConContrasenaIncorrecta(): void
    {
        $this->crearUsuario(self::EMAIL, activo: false);

        $this->assertRechazado(self::EMAIL, 'otra-clave', self::ERROR_GENERICO);
    }

    #[TestDox('Tras 5 fallos con un correo se bloquea 15 minutos, aunque después escriba la contraseña correcta')]
    public function testBloqueoPorCorreo(): void
    {
        $this->crearUsuario(self::EMAIL);
        for ($i = 0; $i < 5; $i++) {
            $this->assertRechazado(self::EMAIL, 'otra-clave', self::ERROR_GENERICO);
        }
        $antes = $this->contarIntentos(self::EMAIL);

        $this->assertRechazado(self::EMAIL, self::CLAVE, 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.');
        $this->assertSame($antes, $this->contarIntentos(self::EMAIL), 'Mientras dura el bloqueo, los intentos no se registran (no lo alargan).');
    }

    #[TestDox('Si al bloqueo le queda menos de un minuto, el mensaje dice «1 minuto»')]
    public function testBloqueoUnMinuto(): void
    {
        $this->crearUsuario(self::EMAIL);
        $this->fallos(5, self::EMAIL, self::IP, haceMinutos: 14);

        $this->assertRechazado(self::EMAIL, self::CLAVE, 'Demasiados intentos fallidos. Espera 1 minuto e inténtalo de nuevo.');
    }

    #[TestDox('Los fallos de hace más de 15 minutos ya no cuentan para el bloqueo')]
    public function testVentanaDeQuinceMinutos(): void
    {
        $this->crearUsuario(self::EMAIL);
        $this->fallos(5, self::EMAIL, self::IP, haceMinutos: 16);

        $resultado = $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP);

        $this->assertSame(self::EMAIL, $resultado['usuario']['email']);
    }

    #[TestDox('Un ingreso correcto reinicia la cuenta de fallos del correo')]
    public function testIngresoCorrectoReiniciaElConteo(): void
    {
        $this->crearUsuario(self::EMAIL);
        $this->fallos(4, self::EMAIL, self::IP);
        $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP);
        $this->fallos(4, self::EMAIL, self::IP);

        $resultado = $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP);

        $this->assertSame(self::EMAIL, $resultado['usuario']['email'], 'Solo cuentan los 4 fallos posteriores al último ingreso.');
    }

    #[TestDox('Tras 30 fallos desde una misma IP (con cualquier correo) se bloquea esa IP, no las demás')]
    public function testBloqueoPorIp(): void
    {
        $this->crearUsuario(self::EMAIL);
        for ($i = 1; $i <= 30; $i++) {
            $this->fallos(1, "persona{$i}@udec.cl", '10.0.0.9');
        }

        $this->assertRechazado(self::EMAIL, self::CLAVE, 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.', '10.0.0.9');

        $desdeOtraIp = $this->login->autenticar(self::EMAIL, self::CLAVE, '10.0.0.2');
        $this->assertSame(self::EMAIL, $desdeOtraIp['usuario']['email']);
    }

    #[TestDox('Una contraseña guardada con un cifrado antiguo se actualiza al ingresar, y sigue sirviendo')]
    public function testRehash(): void
    {
        $id = $this->crearUsuario(self::EMAIL, costo: 4);

        $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP);

        $hash = (string) Database::fetchValue('SELECT password_hash FROM usuarios WHERE id = ?', [$id]);
        $this->assertFalse(password_needs_rehash($hash, PASSWORD_DEFAULT), 'Quedó con el cifrado actual.');
        $this->assertTrue(password_verify(self::CLAVE, $hash));
        $this->assertSame(self::EMAIL, $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP)['usuario']['email']);
    }

    #[TestDox('Al ingresar se borran los registros de intentos de hace más de 90 días')]
    public function testLimpiezaDeRegistrosAntiguos(): void
    {
        $this->crearUsuario(self::EMAIL);
        Database::insert(
            "INSERT INTO intentos_login (email, ip, exitoso, creado_en) VALUES ('viejo@udec.cl', ?, 0, NOW() - INTERVAL 91 DAY)",
            [self::IP]
        );
        Database::insert(
            "INSERT INTO intentos_login (email, ip, exitoso, creado_en) VALUES ('reciente@udec.cl', ?, 0, NOW() - INTERVAL 89 DAY)",
            [self::IP]
        );

        $this->login->autenticar(self::EMAIL, self::CLAVE, self::IP);

        $this->assertSame(0, $this->contarIntentos('viejo@udec.cl'), 'El de hace 91 días se borró.');
        $this->assertSame(1, $this->contarIntentos('reciente@udec.cl'), 'El de hace 89 días se conserva.');
    }

    /** El ingreso se rechaza con exactamente ese mensaje. */
    private function assertRechazado(string $email, string $clave, string $mensaje, string $ip = self::IP): void
    {
        try {
            $this->login->autenticar($email, $clave, $ip);
        } catch (LoginException $e) {
            $this->assertSame($mensaje, $e->getMessage());

            return;
        }

        $this->fail("Se esperaba que no entrara: «{$mensaje}».");
    }
}
