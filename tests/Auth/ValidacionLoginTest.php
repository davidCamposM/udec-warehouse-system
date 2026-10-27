<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Core\ValidationException;
use App\Core\Validator;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * El formulario del login: las mismas reglas que aplica LoginController::login antes de verificar la cuenta.
 */
#[TestDox('Formulario de inicio de sesión')]
final class ValidacionLoginTest extends TestCase
{
    private const REGLAS = [
        'email' => 'requerido|email|max:190',
        'password' => 'requerido',
    ];

    #[TestDox('El correo escrito con mayúsculas se compara en minúsculas')]
    public function testCorreoEnMinusculas(): void
    {
        $datos = Validator::validate(['email' => 'Docente@UdeC.cl', 'password' => 'x'], self::REGLAS);

        $this->assertSame('docente@udec.cl', $datos['email']);
    }

    #[TestDox('Un correo mal escrito se rechaza')]
    public function testCorreoInvalido(): void
    {
        $this->assertSame('email', $this->campoConError(['email' => 'docente-udec.cl', 'password' => 'x']));
    }

    #[TestDox('Un correo de más de 190 letras se rechaza')]
    public function testCorreoMuyLargo(): void
    {
        $largo = str_repeat('a', 183) . '@udec.cl';

        $this->assertSame('email', $this->campoConError(['email' => $largo, 'password' => 'x']));
    }

    #[TestDox('Sin contraseña no se puede enviar')]
    public function testContrasenaVacia(): void
    {
        $this->assertSame('password', $this->campoConError(['email' => 'docente@udec.cl', 'password' => '']));
    }

    #[TestDox('Sin correo no se puede enviar')]
    public function testCorreoVacio(): void
    {
        $this->assertSame('email', $this->campoConError(['email' => '', 'password' => 'x']));
    }

    /** @param array<string, string> $datos */
    private function campoConError(array $datos): string
    {
        try {
            Validator::validate($datos, self::REGLAS);
        } catch (ValidationException $e) {
            $campos = array_keys($e->errors());
            $this->assertCount(1, $campos, 'Solo ese campo tiene error.');

            return $campos[0];
        }

        $this->fail('Se esperaba un error de validación.');
    }
}
