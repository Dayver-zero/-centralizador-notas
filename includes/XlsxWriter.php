<?php
/**
 * XlsxWriter: generador minimo de archivos .xlsx (OpenXML) sin dependencias.
 *
 * Soporta: multiples hojas, celdas de texto/numeros, celdas combinadas
 * (mergeCells), anchos de columna, alto de fila, estilos basicos (negrita,
 * centrado, bordes, rotacion 90°), orientacion de pagina y margenes.
 */
class XlsxWriter {
    private $curSheet;
    private $sheets = [];

    // Estilos: indice => [negrita, fuenteSize, alineacion, borde, rotacion, relleno]
    private $styles = [];
    private $fontIds = [];
    private $fontCount = 1;
    private $borderIds = [];
    private $borderCount = 1;

    public function __construct() {
        $this->styles[0] = ['b' => false, 'sz' => 11, 'h' => 'l', 'f' => 'Times New Roman', 'border' => 0, 'rot' => 0, 'fill' => 0];
    }

    public function addSheet($nombre, $orientacion = 'landscape', $tituloColumna = null, $estiloTitulo = 0) {
        $this->curSheet = [
            'nombre' => $nombre,
            'orientacion' => $orientacion,
            'cols' => [],
            'rows' => [],
            'merges' => [],
            'nextId' => 1,
        ];
        $this->sheets[] = &$this->curSheet;
        return $this;
    }

    public function setColWidth($letra, $ancho) {
        $idx = $this->colNum($letra);
        $this->curSheet['cols'][$idx] = max(0, (float) $ancho);
        return $this;
    }

    private function colNum($letra) {
        $letras = strtoupper($letra);
        $n = 0;
        for ($i = 0; $i < strlen($letras); $i++) {
            $n = $n * 26 + (ord($letras[$i]) - 64);
        }
        return $n;
    }

    private function ref($col, $fila) {
        $s = '';
        $c = $col;
        while ($c > 0) {
            $c--;
            $s = chr(65 + ($c % 26)) . $s;
            $c = intdiv($c, 26);
        }
        return $s . $fila;
    }

    public function setCell($ref, $valor, $estilo = 0) {
        $col = 0; $fila = 0;
        if (preg_match('/^([A-Za-z]+)(\d+)$/', $ref, $m)) {
            $col = $this->colNum($m[1]);
            $fila = (int) $m[2];
        }
        $this->curSheet['rows'][$fila][$col] = [
            'valor' => $valor,
            'estilo' => (int) $estilo,
            'num' => is_int($valor) || is_float($valor),
        ];
        return $this;
    }

    public function setRowHeight($fila, $alto) {
        if (!isset($this->curSheet['rows'][$fila])) {
            $this->curSheet['rows'][$fila] = [];
        }
        $this->curSheet['rows'][$fila]['__ht'] = max(0, (float) $alto);
        return $this;
    }

    public function merge($rango) {
        $this->curSheet['merges'][] = $rango;
        return $this;
    }

    public function addStyle($opciones = []) {
        $id = count($this->styles);
        $this->styles[$id] = array_merge([
            'b' => false, 'sz' => 11, 'h' => 'l', 'f' => 'Times New Roman', 'border' => 0, 'rot' => 0, 'fill' => 0, 'wrap' => false,
        ], $opciones);
        return $id;
    }

    private function styleIdEstilos() {
        foreach ($this->styles as $s) {
            $fontKey  = $this->fontKey($s);
            $borderKey = (int) $s['border'];
            if (!isset($this->fontIds[$fontKey])) {
                $this->fontIds[$fontKey] = $this->fontCount++;
            }
            if (!isset($this->borderIds[$borderKey])) {
                $this->borderIds[$borderKey] = $this->borderCount++;
            }
        }
        return null;
    }

    private function fontKey($s) {
        return (int) $s['b'] . '|' . (int) $s['sz'] . '|' . (string) $s['f'];
    }

    private function xmlCell($col, $fila, $celda) {
        $estilo = $this->styles[$celda['estilo']];
        $ref = $this->ref($col, $fila);
        $attrs = ' r="' . $ref . '"';
        $attrs .= ' s="' . $celda['estilo'] . '"';
        if ($celda['num']) {
            $val = (float) $celda['valor'];
            return '<c' . $attrs . '><v>' . $this->num($val) . '</v></c>';
        }
        $texto = (string) $celda['valor'];
        if ($texto !== '') {
            $attrs .= ' t="inlineStr"';
            $texto = $this->esc($texto);
            return '<c' . $attrs . '><is><t xml:space="preserve">' . $texto . '</t></is></c>';
        }
        return '<c' . $attrs . '/>';
    }

    private function num($v) {
        return rtrim(rtrim(sprintf('%.10F', $v), '0'), '.');
    }

    private function esc($t) {
        return htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    }

    public function build($nombreArchivo) {
        foreach ($this->sheets as $i => $hoja) {
            $this->sheets[$i]['id'] = $i + 1;
        }
        $this->styleIdEstilos();

        $fh = @fopen($nombreArchivo, 'wb');
        if ($fh === false) {
            return false;
        }
        fwrite($fh, $this->zipBytes());
        fclose($fh);
        return true;
    }

    private function zipBytes() {
        $entradas = [
            '[Content_Types].xml' => $this->xmlContentTypes(),
            '_rels/.rels' => $this->xmlRelsRaiz(),
            'xl/workbook.xml' => $this->xmlWorkbook(),
            'xl/_rels/workbook.xml.rels' => $this->xmlWorkbookRels(),
            'xl/styles.xml' => $this->xmlStyles(),
        ];
        foreach ($this->sheets as $hoja) {
            $entradas['xl/worksheets/sheet' . $hoja['id'] . '.xml'] = $this->xmlSheet($hoja);
        }

        $metodo = function_exists('gzdeflate') ? 8 : 0;
        $local = '';
        $central = '';
        $offset = 0;
        foreach ($entradas as $nombre => $contenido) {
            $dato = $metodo === 8 ? gzdeflate($contenido, 9) : $contenido;
            $crc = (int) sprintf('%u', crc32($contenido));
            $tam = strlen($contenido);
            $tamC = strlen($dato);
            $largoN = strlen($nombre);
            $cabLocal = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, $metodo, 0, 0, $crc, $tamC, $tam, $largoN, 0);
            $local .= $cabLocal . $nombre . $dato;
            $cabCentral = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, $metodo, 0, 0, $crc, $tamC, $tam, $largoN, 0, 0, 0, 0, 0, $offset);
            $central .= $cabCentral . $nombre;
            $offset += strlen($cabLocal) + $largoN + $tamC;
        }
        $fin = pack('VvvvvVVv', 0x06054b50, 0, 0, count($entradas), count($entradas), strlen($central), $offset, 0);
        return $local . $central . $fin;
    }

    private function xmlContentTypes() {
        $overrides = '';
        foreach ($this->sheets as $hoja) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $hoja['id'] . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $overrides
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function xmlRelsRaiz() {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xmlWorkbook() {
        $hojas = '';
        foreach ($this->sheets as $hoja) {
            $hojas .= '<sheet name="' . $this->esc($hoja['nombre']) . '" sheetId="' . $hoja['id'] . '" r:id="rId' . $hoja['id'] . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $hojas . '</sheets></workbook>';
    }

    private function xmlWorkbookRels() {
        $rels = '';
        foreach ($this->sheets as $hoja) {
            $rels .= '<Relationship Id="rId' . $hoja['id'] . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $hoja['id'] . '.xml"/>';
        }
        $next = count($this->sheets) + 1;
        $rels .= '<Relationship Id="rId' . $next . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels . '</Relationships>';
    }

    private function xmlSheet($hoja) {
        ksort($hoja['rows']);
        $filas = '';
        foreach ($hoja['rows'] as $fila => $celdas) {
            $atributo = isset($celdas['__ht']) ? ' ht="' . $this->num($celdas['__ht']) . '" customHeight="1"' : '';
            unset($celdas['__ht']);
            ksort($celdas);
            $cuerpo = '';
            foreach ($celdas as $col => $celda) {
                $cuerpo .= $this->xmlCell($col, $fila, $celda);
            }
            $filas .= '<row r="' . $fila . '"' . $atributo . '>' . $cuerpo . '</row>';
        }

        $cols = '';
        ksort($hoja['cols']);
        foreach ($hoja['cols'] as $idx => $ancho) {
            $cols .= '<col min="' . $idx . '" max="' . $idx . '" width="' . $this->num((float) $ancho) . '" customWidth="1"/>';
        }
        if ($cols !== '') {
            $cols = '<cols>' . $cols . '</cols>';
        }

        $merges = '';
        if (!empty($hoja['merges'])) {
            $lista = '';
            foreach ($hoja['merges'] as $rango) {
                $lista .= '<mergeCell ref="' . $rango . '"/>';
            }
            $merges = '<mergeCells count="' . count($hoja['merges']) . '">' . $lista . '</mergeCells>';
        }

        $orientacion = $hoja['orientacion'] === 'portrait' ? 'portrait' : 'landscape';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $cols
            . '<sheetData>' . $filas . '</sheetData>'
            . $merges
            . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="' . $orientacion . '"/>'
            . '</worksheet>';
    }

    private function xmlStyles() {
        $fonts = '';
        $fontOrder = [];
        foreach ($this->fontIds as $clave => $id) {
            $fontOrder[$id] = $clave;
        }
        ksort($fontOrder);
        foreach ($fontOrder as $clave) {
            list ($b, $sz, $f) = explode('|', $clave, 3);
            $bStr = $b ? '<b/>' : '';
            $fonts .= '<font>' . $bStr . '<sz val="' . $sz . '"/><name val="' . $this->esc($f) . '"/></font>';
        }

        // fill 0 = none, 1 = gris cabecera, 2 = verde estadisticas
        $fills = '<fill><patternFill patternType="none"/></fill>'
               . '<fill><patternFill patternType="solid"><fgColor theme="4" tint="0.79998168889431442"/><bgColor indexed="64"/></patternFill></fill>'
               . '<fill><patternFill patternType="solid"><fgColor rgb="FFD6E3BC"/><bgColor indexed="64"/></patternFill></fill>';

        // border 0 = ninguno, 1 = thin (todos los lados)
        $borders = '<border><left/><right/><top/><bottom/><diagonal/></border>'
                 . '<border><left style="thin"><color indexed="64"/></left><right style="thin"><color indexed="64"/></right><top style="thin"><color indexed="64"/></top><bottom style="thin"><color indexed="64"/></bottom><diagonal/></border>';

        $xfs = '';
        foreach ($this->styles as $id => $s) {
            $fontKey = $this->fontKey($s);
            $fontId = $this->fontIds[$fontKey];
            $borderId = $this->borderIds[(int) $s['border']];
            $align = '';
if (in_array($s['h'], ['l', 'c', 'r'], true)) {
                $hp = ['l' => 'left', 'c' => 'center', 'r' => 'right'][$s['h']];
                $rot = (int) $s['rot'] > 0 ? ' textRotation="90"' : '';
                $wrap = !empty($s['wrap']) ? ' wrapText="1"' : '';
                $align = ' applyAlignment="1"><alignment horizontal="' . $hp . '" vertical="center"' . $rot . $wrap . '/>';
            } else {
                $align = '><alignment vertical="center"/>';
            }
            $fill = $s['fill'] ? ' applyFill="1" fillId="' . (int) $s['fill'] . '"' : ' fillId="0"';
            $xfs .= '<xf numFmtId="0" fontId="' . $fontId . '"' . $fill . ' borderId="' . $borderId . '" xfId="0" applyFont="1"' . ($s['border'] ? ' applyBorder="1"' : '') . $align . '</xf>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="' . count($this->fontIds) . '">' . $fonts . '</fonts>'
            . '<fills count="3">' . $fills . '</fills>'
            . '<borders count="2">' . $borders . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($this->styles) . '">' . $xfs . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}