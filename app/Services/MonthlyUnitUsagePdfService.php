<?php

namespace App\Services;

use App\Support\QuantityFormatter;

class MonthlyUnitUsagePdfService
{
    /** @param array<string, mixed> $report */
    public function render(array $report, string $title): string
    {
        $rows = $report['unit_summaries']->all();
        $pages = array_chunk($rows, 20);

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
                $pageRows,
                $report,
                $title,
                $pageIndex + 1,
                count($pages),
                $pageIndex === count($pages) - 1,
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
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $report
     */
    private function pageContent(array $rows, array $report, string $title, int $page, int $pageCount, bool $isLastPage): string
    {
        $commands = [
            '0.07 0.14 0.17 rg 0 525 842 70 re f',
            $this->text(24, 563, $title, 16, true, [1, 1, 1]),
            $this->text(24, 543, 'Periode: '.$report['date_from'].' s/d '.$report['date_to'], 9, false, [0.82, 0.9, 0.91]),
            $this->text(620, 563, 'VHS FUEL MONITORING', 10, true, [1, 1, 1]),
            $this->text(620, 546, 'Monthly Unit Consumption Report', 8, false, [0.82, 0.9, 0.91]),
        ];

        $totals = $report['totals'];
        $commands[] = $this->text(24, 507, 'TOTAL BBM', 7, true, [0.35, 0.4, 0.43]);
        $commands[] = $this->text(24, 490, $this->number($totals['fuel']).' L', 14, true, [0.07, 0.14, 0.17]);
        $commands[] = $this->text(175, 507, 'TRANSAKSI', 7, true, [0.35, 0.4, 0.43]);
        $commands[] = $this->text(175, 490, (string) $totals['refueling_count'], 14, true, [0.07, 0.14, 0.17]);
        $commands[] = $this->text(285, 507, 'WORKING HOURS', 7, true, [0.35, 0.4, 0.43]);
        $commands[] = $this->text(285, 490, $this->number($totals['working_hours']), 14, true, [0.07, 0.14, 0.17]);
        $commands[] = $this->text(440, 507, 'LANGSUNG TERMINAL', 7, true, [0.35, 0.4, 0.43]);
        $commands[] = $this->text(440, 490, $this->number($totals['direct_fuel']).' L', 14, true, [0.07, 0.14, 0.17]);
        $commands[] = $this->text(620, 507, 'MOBIL TANGKI', 7, true, [0.35, 0.4, 0.43]);
        $commands[] = $this->text(620, 490, $this->number($totals['mobile_tanker_fuel']).' L', 14, true, [0.07, 0.14, 0.17]);

        $columns = [
            ['No', 24, 28], ['Unit', 52, 126], ['Jenis', 178, 118], ['Langsung', 296, 66],
            ['Mobil Tangki', 362, 76], ['BBM (L)', 438, 70], ['Trx', 508, 38],
            ['Working', 546, 64], ['Produksi', 610, 66], ['L/Jam', 676, 62], ['L/Prod', 738, 78],
        ];
        $tableTop = 462;
        $rowHeight = 18;
        $commands[] = '0.12 0.22 0.25 rg 24 '.($tableTop - $rowHeight).' 792 '.$rowHeight.' re f';

        foreach ($columns as [$label, $x, $width]) {
            $commands[] = $this->text($x + 3, $tableTop - 12, $label, 7, true, [1, 1, 1]);
            $commands[] = '0.78 0.82 0.83 RG 0.4 w '.$x.' '.($tableTop - $rowHeight).' m '.$x.' '.$tableTop.' l S';
        }

        $y = $tableTop - $rowHeight;

        foreach ($rows as $index => $row) {
            $y -= $rowHeight;
            $background = $index % 2 === 0 ? '0.96 0.98 0.98' : '1 1 1';
            $commands[] = $background.' rg 24 '.$y.' 792 '.$rowHeight.' re f';
            $values = [
                (string) (($page - 1) * 20 + $index + 1),
                $row['unit']->unit_code.' - '.$row['unit']->unit_name,
                $row['unit']->unit_type,
                $this->number($row['direct_fuel']),
                $this->number($row['mobile_tanker_fuel']),
                $this->number($row['fuel']),
                (string) $row['refueling_count'],
                $this->number($row['working_hours']),
                $this->number($row['production']),
                $this->number($row['liter_per_hour']),
                $this->number($row['liter_per_production']),
            ];

            foreach ($columns as $columnIndex => [, $x, $width]) {
                $commands[] = $this->text($x + 3, $y + 6, $this->truncate($values[$columnIndex], $width), 6.8, $columnIndex === 1, [0.12, 0.16, 0.18]);
                $commands[] = '0.84 0.87 0.88 RG 0.35 w '.$x.' '.$y.' m '.$x.' '.($y + $rowHeight).' l S';
            }

            $commands[] = '0.84 0.87 0.88 RG 0.35 w 24 '.$y.' m 816 '.$y.' l S';
        }

        if ($isLastPage) {
            $commands[] = $this->text(24, 56, 'Catatan: rasio dihitung dari total BBM dibagi total working hours/produksi pada periode laporan.', 7, false, [0.35, 0.4, 0.43]);
        }

        $commands[] = '0.82 0.85 0.86 RG 0.4 w 24 36 m 816 36 l S';
        $commands[] = $this->text(24, 20, 'Copyright '.date('Y').' PT Indra Angkola Energy', 7, false, [0.4, 0.45, 0.47]);
        $commands[] = $this->text(755, 20, 'Page '.$page.' / '.$pageCount, 7, false, [0.4, 0.45, 0.47]);

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

        return $pdf."trailer\n<< /Size ".($maxId + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }

    /** @param array{0: float, 1: float, 2: float} $color */
    private function text(float $x, float $y, string $text, float $size, bool $bold, array $color): string
    {
        [$red, $green, $blue] = $color;
        $font = $bold ? 'F2' : 'F1';

        return sprintf(
            'BT %.2F %.2F %.2F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET',
            $red,
            $green,
            $blue,
            $font,
            $size,
            $x,
            $y,
            $this->escape($text),
        );
    }

    private function escape(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $encoded);
    }

    private function truncate(string $value, float $width): string
    {
        $characters = max(4, (int) floor($width / 4.1));

        return mb_strimwidth($value, 0, $characters, '...');
    }

    private function number(float|int|null $value): string
    {
        return $value === null ? '-' : QuantityFormatter::format($value);
    }
}
