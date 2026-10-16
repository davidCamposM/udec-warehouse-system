<?php

declare(strict_types=1);

namespace App\Support;

defined('APP_ROOT') || exit;

/**
 * Un archivo ZIP armado en memoria, sin comprimir (método «stored»). Alcanza para los documentos de Office (.docx), que
 * son un ZIP con archivos XML, y no necesita la extensión zip de PHP.
 */
final class Zip
{
    /** @var list<array{nombre: string, datos: string, crc: int, desde: int}> */
    private array $archivos = [];

    private string $cuerpo = '';

    public function agregar(string $nombre, string $datos): self
    {
        $crc = crc32($datos);
        $desde = strlen($this->cuerpo);
        [$hora, $fecha] = self::fechaDos();

        // Encabezado local: firma, versión 2.0, bandera 0x0800 (nombre en UTF-8), método 0 (sin comprimir).
        $this->cuerpo .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $hora, $fecha, $crc, strlen($datos), strlen($datos), strlen($nombre), 0)
            . $nombre . $datos;
        $this->archivos[] = ['nombre' => $nombre, 'datos' => $datos, 'crc' => $crc, 'desde' => $desde];

        return $this;
    }

    public function contenido(): string
    {
        [$hora, $fecha] = self::fechaDos();
        $directorio = '';
        foreach ($this->archivos as $archivo) {
            $largo = strlen($archivo['datos']);
            $directorio .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50, 20, 20, 0x0800, 0, $hora, $fecha, $archivo['crc'], $largo, $largo,
                strlen($archivo['nombre']), 0, 0, 0, 0, 0, $archivo['desde']
            ) . $archivo['nombre'];
        }

        $total = count($this->archivos);

        return $this->cuerpo . $directorio
            . pack('VvvvvVVv', 0x06054b50, 0, 0, $total, $total, strlen($directorio), strlen($this->cuerpo), 0);
    }

    /** @return array{0: int, 1: int} la hora y la fecha de ahora, en el formato de MS-DOS que usa ZIP */
    private static function fechaDos(): array
    {
        $ahora = getdate();

        return [
            ($ahora['hours'] << 11) | ($ahora['minutes'] << 5) | intdiv($ahora['seconds'], 2),
            (($ahora['year'] - 1980) << 9) | ($ahora['mon'] << 5) | $ahora['mday'],
        ];
    }
}
