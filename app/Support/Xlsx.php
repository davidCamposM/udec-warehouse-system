<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;

defined('APP_ROOT') || exit;

/**
 * Un libro Excel (.xlsx) armado a mano, sin librerías: una o más hojas, cada una con filas de texto (un título, una
 * línea gris) y tablas (fila de títulos en negrita con fondo gris; si se pide, una fila de total en negrita). La primera
 * tabla de cada hoja lleva el filtro de Excel y queda fija al desplazarse. Los números van como números (Excel puede
 * sumarlos) y las fechas como fechas (se ordenan y filtran por mes).
 *
 *   $libro = new Xlsx();
 *   $libro->hoja('Resumen', [30, 12])->fila(['Entradas y salidas'], 'titulo')->tabla(['Concepto', 'Unidades'], $filas);
 *   $contenido = $libro->contenido();
 */
final class Xlsx
{
    /** Estilo => número de formato de celda (cellXfs de styles.xml). */
    private const ESTILOS = [
        'normal' => 0,
        'encabezado' => 1,
        'titulo' => 2,
        'total' => 3,
        'fecha' => 4,
        'gris' => 5,
        'negrita' => 6,
        'subtitulo' => 7,
        'alterno' => 8,
        'fecha-alterno' => 9,
        'numero' => 10,
        'numero-alterno' => 11,
    ];

    /** @var list<array{nombre: string, anchos: list<int>, filas: list<string>, fija: ?int, fijaColumnas: int, filtro: ?string, merges: list<string>}> */
    private array $hojas = [];

    /**
     * Agrega una hoja; las filas y tablas que siguen van en ella.
     *
     * @param list<int> $anchos ancho de cada columna, en caracteres
     */
    public function hoja(string $nombre, array $anchos, int $fijaColumnas = 0): self
    {
        // Hasta 31 letras y sin los caracteres que Excel no acepta en el nombre de una hoja.
        $nombre = mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $nombre), 0, 31);
        $this->hojas[] = ['nombre' => $nombre, 'anchos' => $anchos, 'filas' => [], 'fija' => null, 'fijaColumnas' => max(0, min($fijaColumnas, count($anchos) - 1)), 'filtro' => null, 'merges' => []];

        return $this;
    }

    /**
     * Una fila suelta (un título, una línea de datos, una fila vacía).
     *
     * @param list<string|int|float|DateTimeInterface|null> $celdas
     * @param 'normal'|'alterno'|'titulo'|'subtitulo'|'gris'|'negrita'|'total'|'encabezado' $estilo
     */
    public function fila(array $celdas = [], string $estilo = 'normal'): self
    {
        $hoja = array_key_last($this->hojas);
        $numero = count($this->hojas[$hoja]['filas']) + 1;
        $this->hojas[$hoja]['filas'][] = self::xmlFila($numero, $celdas, $estilo, $this->hojas[$hoja]['anchos']);
        if (count($celdas) === 1 && count($this->hojas[$hoja]['anchos']) > 1
            && in_array($estilo, ['titulo', 'subtitulo', 'gris'], true)) {
            $this->hojas[$hoja]['merges'][] = 'A' . $numero . ':' . self::columna(count($this->hojas[$hoja]['anchos']) - 1) . $numero;
        }

        return $this;
    }

    /**
     * Una tabla: la fila de títulos y una fila por registro; con $total, la última fila va en negrita con una línea
     * arriba. Después, una fila vacía.
     *
     * @param list<string>                                        $titulos
     * @param list<list<string|int|float|DateTimeInterface|null>> $filas
     */
    public function tabla(array $titulos, array $filas, bool $total = false): self
    {
        $hoja = array_key_last($this->hojas);
        $inicio = count($this->hojas[$hoja]['filas']) + 1;
        $this->fila($titulos, 'encabezado');
        foreach ($filas as $i => $fila) {
            $this->fila($fila, $total && $i === count($filas) - 1 ? 'total' : ($i % 2 === 0 ? 'normal' : 'alterno'));
        }

        // La primera tabla de la hoja: fija al desplazarse y con el filtro de Excel (sin la fila de total).
        if ($this->hojas[$hoja]['filtro'] === null) {
            $hasta = $inicio + count($filas) - ($total ? 1 : 0);
            $this->hojas[$hoja]['fija'] = $inicio;
            $this->hojas[$hoja]['filtro'] = 'A' . $inicio . ':' . self::columna(count($titulos) - 1) . max($inicio, $hasta);
        }

        return $this->fila();
    }

    /** El archivo .xlsx listo para descargar. */
    public function contenido(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $ns = 'xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"';
        $nsR = 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $zip = new Zip();

        $hojas = '';
        $relaciones = '';
        $tipos = '';
        foreach ($this->hojas as $i => $hoja) {
            $n = $i + 1;
            $columnas = '';
            foreach ($hoja['anchos'] as $c => $ancho) {
                $columnas .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . $ancho . '" customWidth="1"/>';
            }
            $columnasFijas = $hoja['fijaColumnas'];
            $panel = $hoja['fija'] !== null
                ? '<pane' . ($columnasFijas > 0 ? ' xSplit="' . $columnasFijas . '"' : '')
                    . ' ySplit="' . $hoja['fija'] . '" topLeftCell="' . self::columna($columnasFijas)
                    . ($hoja['fija'] + 1) . '" activePane="' . ($columnasFijas > 0 ? 'bottomRight' : 'bottomLeft')
                    . '" state="frozen"/>'
                : '';
            $merges = $hoja['merges'] === [] ? '' : '<mergeCells count="' . count($hoja['merges']) . '">'
                . implode('', array_map(static fn (string $ref): string => '<mergeCell ref="' . $ref . '"/>', $hoja['merges']))
                . '</mergeCells>';

            $zip->agregar("xl/worksheets/sheet{$n}.xml", $xml . "<worksheet {$ns}>"
                . '<sheetPr><tabColor rgb="FF477E46"/></sheetPr>'
                . '<sheetViews><sheetView workbookViewId="0"' . ($i === 0 ? ' tabSelected="1"' : '') . '>' . $panel . '</sheetView></sheetViews>'
                . '<sheetFormatPr defaultRowHeight="26"/>'
                . ($columnas !== '' ? '<cols>' . $columnas . '</cols>' : '')
                . '<sheetData>' . implode('', $hoja['filas']) . '</sheetData>'
                . ($hoja['filtro'] !== null ? '<autoFilter ref="' . $hoja['filtro'] . '"/>' : '') . $merges
                . '</worksheet>');

            $hojas .= '<sheet name="' . self::xml($hoja['nombre']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $relaciones .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $tipos .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $estilos = count($this->hojas) + 1;

        $zip->agregar('xl/styles.xml', self::estilosXml($xml, $ns));

        $zip->agregar('xl/workbook.xml', $xml . "<workbook {$ns} {$nsR}><sheets>" . $hojas . '</sheets></workbook>');
        $zip->agregar('xl/_rels/workbook.xml.rels', $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relaciones
            . '<Relationship Id="rId' . $estilos . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->agregar('_rels/.rels', $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->agregar('[Content_Types].xml', $xml . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $tipos . '</Types>');

        return $zip->contenido();
    }

    /** @param list<string|int|float|DateTimeInterface|null> $celdas */
    private static function xmlFila(int $numero, array $celdas, string $estilo, array $anchos): string
    {
        $alto = match ($estilo) {
            'titulo' => 42, 'subtitulo' => 30, 'encabezado' => 34, 'total' => 30, 'gris' => 24,
            default => 28,
        };
        if (in_array($estilo, ['normal', 'alterno'], true)) {
            foreach ($celdas as $i => $valor) {
                if (is_string($valor) && $valor !== '') {
                    $lineas = max(1, (int) ceil(mb_strlen($valor) / max(8, ($anchos[$i] ?? 20) - 3)));
                    $alto = max($alto, min(130, $lineas * 15 + 10));
                }
            }
        }
        $xml = '<row r="' . $numero . '" ht="' . $alto . '" customHeight="1">';
        foreach ($celdas as $i => $valor) {
            $ref = self::columna($i) . $numero;
            $s = self::ESTILOS[$estilo];
            if ($valor instanceof DateTimeInterface) {
                // Excel cuenta los días desde el 30/12/1899.
                $dias = intdiv((int) $valor->format('U') + (int) $valor->format('Z'), 86400) + 25569;
                $xml .= '<c r="' . $ref . '" s="' . (in_array($estilo, ['normal', 'alterno'], true) ? self::ESTILOS[$estilo === 'alterno' ? 'fecha-alterno' : 'fecha'] : $s) . '"><v>' . $dias . '</v></c>';
            } elseif (is_int($valor) || is_float($valor)) {
                $numerico = $estilo === 'normal' ? self::ESTILOS['numero'] : ($estilo === 'alterno' ? self::ESTILOS['numero-alterno'] : $s);
                $xml .= '<c r="' . $ref . '" s="' . $numerico . '"><v>' . $valor . '</v></c>';
            } elseif ($valor !== null && $valor !== '') {
                $xml .= '<c r="' . $ref . '"' . ($s > 0 ? ' s="' . $s . '"' : '') . ' t="inlineStr"><is><t xml:space="preserve">'
                    . self::xml($valor) . '</t></is></c>';
            } else {
                $xml .= '<c r="' . $ref . '" s="' . $s . '"/>';
            }
        }

        return $xml . '</row>';
    }

    /** Paleta verde de la aplicación, con rayado suave, texto legible y cifras alineadas. */
    private static function estilosXml(string $xml, string $ns): string
    {
        return $xml . "<styleSheet {$ns}>"
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
            . '<fonts count="6">'
            . '<font><sz val="11"/><color rgb="FF212529"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="16"/><color rgb="FF192C19"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><color rgb="FF59665A"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="12"/><color rgb="FF325931"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF192C19"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="6">'
            . '<fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF325931"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F6F1"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF7FAF7"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE5EFE5"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="4">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left/><right/><top/><bottom style="medium"><color rgb="FF254724"/></bottom><diagonal/></border>'
            . '<border><left/><right/><top style="thin"><color rgb="FF9DBD9D"/></top><bottom/><diagonal/></border>'
            . '<border><left/><right/><top/><bottom style="hair"><color rgb="FFDDE8DD"/></bottom><diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="12">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="5" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1" applyNumberFormat="1"><alignment vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="5" fillId="0" borderId="0" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="4" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1" indent="1"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="4" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1" applyNumberFormat="1"><alignment vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center" indent="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right" vertical="center" indent="1"/></xf>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    /** 0 → A, 25 → Z, 26 → AA */
    private static function columna(int $indice): string
    {
        $letras = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letras = chr(65 + ($n - 1) % 26) . $letras;
        }

        return $letras;
    }

    private static function xml(string $texto): string
    {
        // Sin caracteres de control (no válidos en XML), salvo tabulación y saltos de línea.
        $texto = preg_replace('/[^\P{C}\t\n\r]/u', '', $texto) ?? '';

        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
