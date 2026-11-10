<?php

declare(strict_types=1);

namespace App\Support;

defined('APP_ROOT') || exit;

/** PDF vectorial sencillo: texto, líneas y superficies grises; no requiere Composer ni extensiones. */
final class Pdf
{
    public const ANCHO = 842;  // A4 apaisado, en puntos.
    public const ALTO = 595;

    /** @var list<string> */
    private array $paginas = [];

    public function pagina(): void
    {
        $this->paginas[] = '';
    }

    public function texto(float $x, float $desdeArriba, string $valor, float $tamano = 9, bool $negrita = false, string $alineacion = 'izquierda'): void
    {
        $texto = self::latin($valor);
        if ($alineacion !== 'izquierda') {
            $ancho = self::ancho($valor, $tamano);
            $x -= $alineacion === 'centro' ? $ancho / 2 : $ancho;
        }
        $this->agregar('0 g BT /' . ($negrita ? 'F2' : 'F1') . ' ' . self::n($tamano) . ' Tf 1 0 0 1 '
            . self::n($x) . ' ' . self::n(self::ALTO - $desdeArriba) . ' Tm (' . self::escapar($texto) . ') Tj ET');
    }

    public function linea(float $x1, float $y1, float $x2, float $y2, float $grosor = 0.5): void
    {
        $this->agregar('0 G ' . self::n($grosor) . ' w ' . self::n($x1) . ' ' . self::n(self::ALTO - $y1)
            . ' m ' . self::n($x2) . ' ' . self::n(self::ALTO - $y2) . ' l S');
    }

    public function fondo(float $x, float $y, float $ancho, float $alto, float $gris): void
    {
        $this->agregar(self::n($gris) . ' g ' . self::n($x) . ' ' . self::n(self::ALTO - $y - $alto)
            . ' ' . self::n($ancho) . ' ' . self::n($alto) . ' re f');
    }

    /** @return list<string> */
    public static function partir(string $texto, float $ancho, float $tamano): array
    {
        $palabras = preg_split('/\s+/u', trim($texto)) ?: [];
        $lineas = [];
        $linea = '';
        foreach ($palabras as $palabra) {
            $candidato = $linea === '' ? $palabra : $linea . ' ' . $palabra;
            if ($linea !== '' && self::ancho($candidato, $tamano) > $ancho) {
                $lineas[] = $linea;
                $linea = $palabra;
            } else {
                $linea = $candidato;
            }
        }
        if ($linea !== '' || $lineas === []) {
            $lineas[] = $linea;
        }
        return $lineas;
    }

    public static function ancho(string $texto, float $tamano): float
    {
        // Estimación conservadora para Helvetica; se deja margen dentro de cada celda.
        $latin = self::latin($texto);
        $unidades = 0.0;
        foreach (str_split($latin) as $letra) {
            $unidades += match (true) {
                $letra === ' ' => 0.28,
                str_contains('ilI.,:;!|', $letra) => 0.25,
                str_contains('mwMW@', $letra) => 0.82,
                ctype_upper($letra) => 0.63,
                default => 0.54,
            };
        }
        return $unidades * $tamano;
    }

    public function contenido(string $pie): string
    {
        $total = count($this->paginas);
        $objetos = [1 => '', 2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>'];
        $hijos = [];
        foreach ($this->paginas as $i => $contenido) {
            $paginaId = 5 + $i * 2;
            $streamId = $paginaId + 1;
            $hijos[] = $paginaId . ' 0 R';
            $piePagina = '0 G 0.5 w 40 39 m 802 39 l S' . "\n"
                . '0 g BT /F1 8 Tf 1 0 0 1 40 23 Tm (' . self::escapar(self::latin($pie)) . ') Tj ET' . "\n"
                . 'BT /F1 8 Tf 1 0 0 1 750 23 Tm (' . self::escapar(self::latin('Página ' . ($i + 1) . ' de ' . $total)) . ') Tj ET' . "\n";
            $stream = $contenido . $piePagina;
            $objetos[$paginaId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
            $objetos[$streamId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
        }
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $hijos) . '] /Count ' . $total . ' >>';
        ksort($objetos);

        $salida = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objetos as $id => $objeto) {
            $offsets[$id] = strlen($salida);
            $salida .= $id . " 0 obj\n" . $objeto . "\nendobj\n";
        }
        $inicioXref = strlen($salida);
        $salida .= 'xref' . "\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objetos); $i++) {
            $salida .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
        }
        return $salida . 'trailer << /Size ' . (count($objetos) + 1) . ' /Root 1 0 R >>' . "\nstartxref\n{$inicioXref}\n%%EOF";
    }

    private function agregar(string $comando): void
    {
        $i = array_key_last($this->paginas);
        $this->paginas[$i] .= $comando . "\n";
    }

    private static function latin(string $texto): string
    {
        $latin = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto);
        return $latin === false ? '' : $latin;
    }

    private static function escapar(string $texto): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\)', ' ', ' '], $texto);
    }

    private static function n(float $n): string
    {
        return rtrim(rtrim(sprintf('%.2F', $n), '0'), '.');
    }
}
