<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Url;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Después del login se vuelve a la página que se quería abrir, solo si es de este sistema (Url::isInternal): una URL
 * manipulada no puede llevar a otro sitio. La base es la de tests/bootstrap.php: http://localhost:8080.
 */
#[TestDox('Regreso a la página anterior después del login')]
final class UrlInternaTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function propias(): array
    {
        return [
            'la raíz' => ['http://localhost:8080'],
            'una página' => ['http://localhost:8080/insumos'],
            'una página con datos' => ['http://localhost:8080/insumos?ver=reponer'],
        ];
    }

    /** @return array<string, array{string}> */
    public static function ajenas(): array
    {
        return [
            'otro sitio' => ['https://otro.cl/login'],
            'un dominio que empieza igual' => ['http://localhost:8080.otro.cl/insumos'],
            'una dirección sin protocolo' => ['//otro.cl/insumos'],
            'una ruta relativa' => ['/insumos'],
            'vacía' => [''],
        ];
    }

    #[DataProvider('propias')]
    #[TestDox('Vuelve a $url')]
    public function testUrlPropia(string $url): void
    {
        $this->assertTrue(Url::isInternal($url));
    }

    #[DataProvider('ajenas')]
    #[TestDox('No redirige a $url')]
    public function testUrlAjena(string $url): void
    {
        $this->assertFalse(Url::isInternal($url));
    }
}
