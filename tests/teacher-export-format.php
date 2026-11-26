<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/TeacherExportFormat.php';
$doc = ['title' => 'Nilai & Soal Kelas XI', 'headers' => ['Nama', 'Nilai', 'Catatan'], 'rows' => [
    ['=HYPERLINK("https://evil.example", "Klik")', '100', "Bahasa Indonesia, <tag> & tanda\nbaris"],
    ['+cmd|calc', 75, "Aman\x01"],
]];
$xlsx = TeacherExportFormat::xlsx($doc);
if (!str_starts_with($xlsx, "PK\x03\x04")) { throw new RuntimeException('Expected a real XLSX ZIP file.'); }
if (!str_contains($xlsx, 't="inlineStr"') || !str_contains($xlsx, '=HYPERLINK') || str_contains($xlsx, '<f>')) {
    throw new RuntimeException('Student-provided spreadsheet values must be literal strings.');
}
if (!str_contains($xlsx, '&lt;tag&gt; &amp;')) { throw new RuntimeException('Spreadsheet XML must escape text.'); }
if (!str_contains($xlsx, '<v>75</v>') || str_contains($xlsx, "Aman\x01")) { throw new RuntimeException('Scores must remain numeric and invalid XML controls must be removed.'); }
$pdf = TeacherExportFormat::pdf($doc);
if (!str_starts_with($pdf, '%PDF-') || !str_contains(substr($pdf, -1024), '%%EOF')) {
    throw new RuntimeException('Expected a complete PDF file.');
}
echo "PASS: export produces real PDF and XLSX with literal spreadsheet strings.\n";
