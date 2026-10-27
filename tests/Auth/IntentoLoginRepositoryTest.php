<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Modules\Auth\IntentoLoginRepository;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\PruebaConBase;

/**
 * El cálculo del bloqueo en la base (ventana deslizante): cuántos segundos faltan para volver a intentar.
 */
#[TestDox('Registro de intentos de ingreso')]
final class IntentoLoginRepositoryTest extends PruebaConBase
{
    private IntentoLoginRepository $intentos;

    protected function setUp(): void
    {
        parent::setUp();
        $this->intentos = new IntentoLoginRepository();
    }

    #[TestDox('Sin fallos no hay espera, ni por correo ni por IP')]
    public function testSinFallosNoHayEspera(): void
    {
        $this->assertSame(0, $this->intentos->esperaPorEmail('docente@udec.cl', 5, 15));
        $this->assertSame(0, $this->intentos->esperaPorIp('10.0.0.1', 30, 15));
    }

    #[TestDox('Con el máximo de fallos recién ocurridos, la espera es de unos 15 minutos (900 segundos)')]
    public function testEsperaConElMaximoDeFallos(): void
    {
        $this->fallos(5, 'docente@udec.cl', '10.0.0.1');

        $this->assertEqualsWithDelta(900, $this->intentos->esperaPorEmail('docente@udec.cl', 5, 15), 5);
        $this->assertEqualsWithDelta(900, $this->intentos->esperaPorIp('10.0.0.1', 5, 15), 5);
    }

    #[TestDox('Con un fallo menos que el máximo no hay espera')]
    public function testUnFalloMenosQueElMaximo(): void
    {
        $this->fallos(4, 'docente@udec.cl', '10.0.0.1');

        $this->assertSame(0, $this->intentos->esperaPorEmail('docente@udec.cl', 5, 15));
    }

    #[TestDox('Los ingresos correctos no cuentan como fallos')]
    public function testLosExitosNoCuentan(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->intentos->registrar('docente@udec.cl', '10.0.0.1', true, null);
        }

        $this->assertSame(0, $this->intentos->esperaPorEmail('docente@udec.cl', 5, 15));
        $this->assertSame(0, $this->intentos->esperaPorIp('10.0.0.1', 5, 15));
    }
}
