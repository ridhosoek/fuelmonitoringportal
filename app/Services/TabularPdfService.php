<?php

namespace App\Services;

use App\Support\QuantityFormatter;

class TabularPdfService
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @param  array<string, string|int|float>  $summary
     */
    public function render(string $title, string $subtitle, array $headers, array $rows, array $summary = []): string
    {
        $rowsPerPage = $summary === [] ? 22 : 19;
        $pages = array_chunk($rows, $rowsPerPage);

        if ($pages === []) {
            $pages = [[]];
        }

        $objects = [
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $pageIds = [];
        $objectId = 5;

        foreach ($pages as $pageIndex => $pageRows) {
            $pageId = $objectId++;
            $contentId = $objectId++;
            $pageIds[] = $pageId;
            $content = $this->pageContent(
                $title,
                $subtitle,
                $headers,
                $pageRows,
                $summary,
                $pageIndex + 1,
                count($pages),
                $pageIndex * $rowsPerPage,
            );
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $objects[$contentId] = '<< /Length '.strlen($content).' >>'."\nstream\n".$content."\nendstream";
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Count '.count($pageIds).' /Kids ['.implode(' ', array_map(fn (int $id): string => $id.' 0 R', $pageIds)).'] >>';
        ksort($objects);

        return $this->compile($objects);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     * @param  array<string, string|int|float>  $summary
     */
    private function pageContent(string $title, string $subtitle, array $headers, array $rows, array $summary, int $page, int $pageCount, int $rowOffset): string
    {
        $commands = [
            '0.07 0.14 0.17 rg 0 525 842 70 re f',
            $this->text(24, 563, $title, 16, true, [1, 1, 1]),
            $this->text(24, 543, $subtitle, 8.5, false, [0.82, 0.9, 0.91]),
            $this->text(660, 563, 'VHS FUEL MONITORING', 9, true, [1, 1, 1]),
            $this->text(660, 547, 'Operational Report', 7.5, false, [0.82, 0.9, 0.91]),
        ];

        $tableTop = 498;

        if ($summary !== []) {
            $x = 24;

            foreach (array_slice($summary, 0, 5, true) as $label => $value) {
                $commands[] = $this->text($x, 510, strtoupper($label), 6.5, true, [0.35, 0.4, 0.43]);
                $commands[] = $this->text($x, 492, (string) $value, 12, true, [0.07, 0.14, 0.17]);
                $x += 155;
            }

            $tableTop = 464;
        }

        $rowHeight = 19;
        $tableWidth = 792.0;
        $columnWidth = $tableWidth / max(1, count($headers));
        $commands[] = '0.12 0.22 0.25 rg 24 '.($tableTop - $rowHeight).' 792 '.$rowHeight.' re f';

        foreach ($headers as $index => $header) {
            $x = 24 + ($index * $columnWidth);
            $commands[] = $this->text($x + 3, $tableTop - 13, $this->truncate((string) $header, $columnWidth), 6.6, true, [1, 1, 1]);
        }

        $y = $tableTop - $rowHeight;

        foreach ($rows as $rowIndex => $row) {
            $y -= $rowHeight;
            $background = $rowIndex % 2 === 0 ? '0.96 0.98 0.98' : '1 1 1';
            $commands[] = $background.' rg 24 '.$y.' 792 '.$rowHeight.' re f';

            foreach ($headers as $columnIndex => $header) {
                $x = 24 + ($columnIndex * $columnWidth);
                $value = $row[$columnIndex] ?? null;
                $commands[] = $this->text($x + 3, $y + 6.5, $this->truncate($this->format($value), $columnWidth), 6.5, $columnIndex === 0, [0.12, 0.16, 0.18]);
                $commands[] = '0.84 0.87 0.88 RG 0.35 w '.$x.' '.$y.' m '.$x.' '.($y + $rowHeight).' l S';
            }

            $commands[] = '0.84 0.87 0.88 RG 0.35 w 24 '.$y.' m 816 '.$y.' l S';
        }

        if ($rows === []) {
            $commands[] = $this->text(24, $y - 28, 'Belum ada data pada filter yang dipilih.', 9, false, [0.35, 0.4, 0.43]);
        }

        $commands[] = '0.82 0.85 0.86 RG 0.4 w 24 36 m 816 36 l S';
        $commands[] = $this->text(24, 20, 'Copyright '.date('Y').' PT Indra Angkola Energy', 7, false, [0.4, 0.45, 0.47]);
        $commands[] = $this->text(720, 20, 'Page '.$page.' / '.$pageCount, 7, false, [0.4, 0.45, 0.47]);
        $rowRange = $rows === [] ? 'Rows 0' : 'Rows '.($rowOffset + 1).'-'.($rowOffset + count($rows));
        $commands[] = $this->text(620, 20, $rowRange, 7, false, [0.4, 0.45, 0.47]);

        return implode("\n", $commands);
    }

    /** @param array<int, string> $objects */
    private function compile(array $objects): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 ".($maxId + 1)."\n0000000000 65535 f \n";

        for ($id = 1; $id <= $maxId; $id++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id])."\n";
        }

        return $pdf."trailer\n<< /Size ".($maxId + 1).' /Root 1 0 R >>'."\nstartxref\n".$xref."\n%%EOF";
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function text(float $x, float $y, string $text, float $size, bool $bold, array $color): string
    {
        [$red, $green, $blue] = $color;

        return sprintf(
            'BT %.2F %.2F %.2F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET',
            $red,
            $green,
            $blue,
            $bold ? 'F2' : 'F1',
            $size,
            $x,
            $y,
            $this->escape($text),
        );
    }

    private function format(string|int|float|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return is_float($value) ? QuantityFormatter::format($value) : (string) $value;
    }

    private function truncate(string $value, float $width): string
    {
        return mb_strimwidth($value, 0, max(4, (int) floor($width / 3.9)), '...');
    }

    private function escape(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $encoded);
    }
}
