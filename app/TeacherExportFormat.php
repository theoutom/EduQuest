<?php
declare(strict_types=1);

final class TeacherExportFormat
{
    public static function pdf(array $document): string
    {
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $html = '<!doctype html><html><head><meta charset="UTF-8"><style>'
            . '@page{margin:36pt}body{font-family:DejaVu Sans,sans-serif;font-size:9pt;color:#182338}'
            . 'h1{font-size:16pt}table{width:100%;border-collapse:collapse;table-layout:fixed}'
            . 'th,td{border:1px solid #cbd5e1;padding:5pt;vertical-align:top;overflow-wrap:break-word}'
            . 'th{background:#eff6ff;text-align:left}tr{page-break-inside:avoid}thead{display:table-header-group}'
            . '</style></head><body><h1>' . $escape($document['title']) . '</h1><table><thead><tr>';
        foreach ($document['headers'] as $header) { $html .= '<th>' . $escape($header) . '</th>'; }
        $html .= '</tr></thead><tbody>';
        foreach ($document['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $value) { $html .= '<td>' . nl2br($escape($value), false) . '</td>'; }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></body></html>';
        $options = new Dompdf\Options();
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot(dirname(__DIR__) . '/vendor/dompdf/dompdf');
        $options->setTempDir(sys_get_temp_dir());
        $options->setFontCache(sys_get_temp_dir());
        $options->setDefaultFont('DejaVu Sans');
        $pdf = new Dompdf\Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('a4', count($document['headers']) > 4 ? 'landscape' : 'portrait');
        $pdf->render();
        return $pdf->output();
    }

    public static function xlsx(array $document): string
    {
        $xml = static fn ($value): string => htmlspecialchars(preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', (string) $value) ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1, 'UTF-8');
        $rows = [[$document['title']], $document['headers'], ...$document['rows']];
        $sheetRows = '';
        $columns = '';
        foreach (array_values($document['headers']) as $index => $header) {
            $number = $index + 1;
            $wide = in_array($header, ['Soal', 'Kode', 'Pilihan', 'Pembahasan'], true);
            $columns .= '<col min="' . $number . '" max="' . $number . '" width="' . ($wide ? '45' : '24') . '" customWidth="1"/>';
        }
        foreach ($rows as $rowIndex => $row) {
            $number = $rowIndex + 1;
            $sheetRows .= '<row r="' . $number . '"' . ($rowIndex >= 2 && count($document['headers']) > 5 ? ' ht="54" customHeight="1"' : '') . '>';
            foreach (array_values($row) as $column => $value) {
                $letters = '';
                for ($n = $column + 1; $n > 0; $n = intdiv($n - 1, 26)) { $letters = chr(65 + (($n - 1) % 26)) . $letters; }
                $cell = $letters . $number;
                $style = $rowIndex === 0 ? 1 : ($rowIndex === 1 ? 2 : 3);
                $sheetRows .= is_int($value)
                    ? '<c r="' . $cell . '" s="' . $style . '" t="n"><v>' . $value . '</v></c>'
                    : '<c r="' . $cell . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $xml($value) . '</t></is></c>';
            }
            $sheetRows .= '</row>';
        }
        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="EduQuest" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="3"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="14"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2563EB"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment wrapText="1" vertical="top"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment wrapText="1" vertical="top"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols>' . $columns . '</cols><sheetData>' . $sheetRows . '</sheetData></worksheet>',
        ];
        return self::zip($files);
    }

    private static function zip(array $files): string
    {
        $body = '';
        $directory = '';
        foreach ($files as $name => $content) {
            $offset = strlen($body);
            $size = strlen($content);
            $crc = crc32($content);
            $nameLength = strlen($name);
            $body .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0) . $name . $content;
            $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset) . $name;
        }
        return $body . $directory . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($directory), strlen($body), 0);
    }
}
