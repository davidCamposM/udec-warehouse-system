<?php

declare(strict_types=1);

namespace App\Support;

defined('APP_ROOT') || exit;

/**
 * Un documento Word (.docx) sencillo, armado a mano: títulos, párrafos y tablas, en hoja carta, en
 * blanco y negro, con Arial. Al pie de cada página, un texto y «Página N de M». Se usa para los informes que se imprimen
 * (por ejemplo, el estado de la bodega).
 */
final class Docx
{
    /** Ancho útil de la hoja carta con márgenes de 2 cm, en veinteavos de punto (twips). */
    public const ANCHO = 9972;

    private string $cuerpo = '';

    private string $pie = '';

    /** Línea chica en mayúsculas sobre el título (la institución). */
    public function membrete(string $texto): self
    {
        return $this->parrafo(mb_strtoupper($texto), ['tamano' => 8, 'gris' => true, 'espaciado' => 40, 'despues' => 0]);
    }

    public function titulo(string $texto): self
    {
        return $this->parrafo($texto, ['tamano' => 20, 'negrita' => true, 'antes' => 120, 'despues' => 60]);
    }

    /** Título de sección: «1. Insumos de consumo», con una línea debajo. */
    public function seccion(string $texto): self
    {
        $this->cuerpo .= '<w:p><w:pPr><w:keepNext/><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="2" w:color="000000"/></w:pBdr>'
            . '<w:spacing w:before="360" w:after="120"/></w:pPr>'
            . self::run($texto, ['tamano' => 12, 'negrita' => true]) . '</w:p>';

        return $this;
    }

    public function subseccion(string $texto): self
    {
        return $this->parrafo($texto, ['negrita' => true, 'antes' => 200, 'despues' => 80, 'seguir' => true]);
    }

    /**
     * @param array{negrita?: bool, gris?: bool, tamano?: int, antes?: int, despues?: int, centrado?: bool,
     *     espaciado?: int, seguir?: bool, linea?: bool} $opciones tamano en puntos; antes, despues y espaciado en twips;
     *     seguir: no separarlo del siguiente; linea: una línea debajo
     */
    public function parrafo(string $texto, array $opciones = []): self
    {
        $pPr = ($opciones['seguir'] ?? false ? '<w:keepNext/>' : '')
            . ($opciones['linea'] ?? false ? '<w:pBdr><w:bottom w:val="single" w:sz="12" w:space="4" w:color="000000"/></w:pBdr>' : '')
            . '<w:spacing w:before="' . ($opciones['antes'] ?? 0) . '" w:after="' . ($opciones['despues'] ?? 120) . '"/>'
            . ($opciones['centrado'] ?? false ? '<w:jc w:val="center"/>' : '');
        $this->cuerpo .= '<w:p><w:pPr>' . $pPr . '</w:pPr>' . self::run($texto, $opciones) . '</w:p>';

        return $this;
    }

    /**
     * Una tabla con fila de títulos (se repite si la tabla sigue en otra página), líneas horizontales finas y, si se
     * pide, la última fila en negrita (un total).
     *
     * @param list<string>       $titulos
     * @param list<list<string>> $filas
     * @param list<int>          $anchos  proporciones de cada columna (se reparten en el ancho de la hoja)
     * @param list<int>          $derecha columnas alineadas a la derecha (números)
     */
    public function tabla(array $titulos, array $filas, array $anchos, array $derecha = [], bool $total = false): self
    {
        $suma = array_sum($anchos);
        $columnas = array_map(static fn (int $ancho): int => intdiv($ancho * self::ANCHO, $suma), $anchos);

        $xml = '<w:tbl><w:tblPr><w:tblW w:w="' . self::ANCHO . '" w:type="dxa"/>'
            . '<w:tblBorders><w:top w:val="single" w:sz="8" w:color="000000"/><w:bottom w:val="single" w:sz="8" w:color="000000"/>'
            . '<w:insideH w:val="single" w:sz="4" w:color="BFBFBF"/></w:tblBorders><w:tblLayout w:type="fixed"/>'
            . '<w:tblCellMar><w:top w:w="50" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="50" w:type="dxa"/>'
            . '<w:right w:w="90" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>';
        foreach ($columnas as $ancho) {
            $xml .= '<w:gridCol w:w="' . $ancho . '"/>';
        }
        $xml .= '</w:tblGrid>';

        $xml .= self::fila($titulos, $columnas, $derecha, ['titulos' => true]);
        foreach ($filas as $i => $fila) {
            $xml .= self::fila($fila, $columnas, $derecha, ['total' => $total && $i === count($filas) - 1]);
        }

        $this->cuerpo .= $xml . '</w:tbl>';

        return $this->parrafo('', ['despues' => 60]);
    }

    /** El texto al pie de cada página (a la izquierda; a la derecha va «Página N de M»). */
    public function pie(string $texto): self
    {
        $this->pie = $texto;

        return $this;
    }

    /** El archivo .docx listo para descargar. */
    public function contenido(): string
    {
        $w = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
        $r = 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";

        $documento = $xml . "<w:document {$w} {$r}><w:body>" . $this->cuerpo
            . '<w:sectPr><w:footerReference w:type="default" r:id="rIdPie"/>'
            . '<w:pgSz w:w="12240" w:h="15840"/>'
            . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="567" w:footer="567" w:gutter="0"/>'
            . '</w:sectPr></w:body></w:document>';

        $campo = static fn (string $nombre): string => '<w:fldSimple w:instr=" ' . $nombre . ' ">'
            . self::run('1', ['tamano' => 8, 'gris' => true]) . '</w:fldSimple>';
        $pie = $xml . "<w:ftr {$w} {$r}><w:p><w:pPr>"
            . '<w:pBdr><w:top w:val="single" w:sz="4" w:space="4" w:color="808080"/></w:pBdr>'
            . '<w:tabs><w:tab w:val="right" w:pos="' . self::ANCHO . '"/></w:tabs><w:spacing w:after="0"/></w:pPr>'
            . self::run($this->pie, ['tamano' => 8, 'gris' => true])
            . '<w:r><w:rPr><w:sz w:val="16"/></w:rPr><w:tab/></w:r>'
            . self::run('Página ', ['tamano' => 8, 'gris' => true]) . $campo('PAGE')
            . self::run(' de ', ['tamano' => 8, 'gris' => true]) . $campo('NUMPAGES')
            . '</w:p></w:ftr>';

        $estilos = $xml . "<w:styles {$w}><w:docDefaults><w:rPrDefault><w:rPr>"
            . '<w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:eastAsia="Arial" w:cs="Arial"/><w:color w:val="000000"/>'
            . '<w:sz w:val="20"/><w:szCs w:val="20"/><w:lang w:val="es-CL"/></w:rPr></w:rPrDefault>'
            . '<w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="264" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            . '<w:style w:type="table" w:default="1" w:styleId="TablaNormal"><w:name w:val="Normal Table"/><w:tblPr>'
            . '<w:tblCellMar><w:left w:w="108" w:type="dxa"/><w:right w:w="108" w:type="dxa"/></w:tblCellMar></w:tblPr></w:style>'
            . '</w:styles>';

        $tipos = $xml . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '</Types>';

        $relaciones = $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '</Relationships>';

        $relacionesDocumento = $xml . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rIdEstilos" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rIdPie" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
            . '</Relationships>';

        $ahora = gmdate('Y-m-d\TH:i:s\Z');
        $propiedades = $xml . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>' . self::xml($this->pie) . '</dc:title><dc:creator>Bodega FCF</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $ahora . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $ahora . '</dcterms:modified>'
            . '</cp:coreProperties>';

        return (new Zip())
            ->agregar('[Content_Types].xml', $tipos)
            ->agregar('_rels/.rels', $relaciones)
            ->agregar('word/document.xml', $documento)
            ->agregar('word/styles.xml', $estilos)
            ->agregar('word/footer1.xml', $pie)
            ->agregar('word/_rels/document.xml.rels', $relacionesDocumento)
            ->agregar('docProps/core.xml', $propiedades)
            ->contenido();
    }

    /**
     * @param list<string> $celdas
     * @param list<int>    $columnas
     * @param list<int>    $derecha
     * @param array{titulos?: bool, total?: bool} $tipo
     */
    private static function fila(array $celdas, array $columnas, array $derecha, array $tipo): string
    {
        $titulos = $tipo['titulos'] ?? false;
        $total = $tipo['total'] ?? false;
        $xml = '<w:tr><w:trPr><w:cantSplit/>' . ($titulos ? '<w:tblHeader/>' : '') . '</w:trPr>';

        foreach ($columnas as $i => $ancho) {
            $bordes = $titulos || $total
                ? '<w:tcBorders><w:' . ($titulos ? 'bottom' : 'top') . ' w:val="single" w:sz="6" w:color="000000"/></w:tcBorders>'
                : '';
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="' . $ancho . '" w:type="dxa"/>' . $bordes
                . ($titulos ? '<w:shd w:val="clear" w:color="auto" w:fill="F2F2F2"/>' : '') . '</w:tcPr>'
                . '<w:p><w:pPr><w:spacing w:after="0"/>' . (in_array($i, $derecha, true) ? '<w:jc w:val="right"/>' : '') . '</w:pPr>'
                . self::run((string) ($celdas[$i] ?? ''), ['negrita' => $titulos || $total, 'tamano' => $titulos ? 9 : 10])
                . '</w:p></w:tc>';
        }

        return $xml . '</w:tr>';
    }

    /** @param array{negrita?: bool, gris?: bool, tamano?: int, espaciado?: int} $opciones */
    private static function run(string $texto, array $opciones = []): string
    {
        if ($texto === '') {
            return '';
        }

        $rPr = ($opciones['negrita'] ?? false ? '<w:b/>' : '')
            . ($opciones['gris'] ?? false ? '<w:color w:val="595959"/>' : '')
            . (isset($opciones['espaciado']) ? '<w:spacing w:val="' . $opciones['espaciado'] . '"/>' : '')
            . (isset($opciones['tamano']) ? '<w:sz w:val="' . ($opciones['tamano'] * 2) . '"/><w:szCs w:val="' . ($opciones['tamano'] * 2) . '"/>' : '');

        return '<w:r>' . ($rPr !== '' ? '<w:rPr>' . $rPr . '</w:rPr>' : '') . '<w:t xml:space="preserve">' . self::xml($texto) . '</w:t></w:r>';
    }

    private static function xml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
