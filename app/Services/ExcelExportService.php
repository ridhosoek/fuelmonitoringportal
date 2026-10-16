<?php

namespace App\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class ExcelExportService
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, scalar|null>>  $rows
     */
    public function download(string $filename, array $headers, iterable $rows): Response
    {
        $binary = $this->toXlsx($headers, $rows);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function downloadOperationalUsage(string $filename, array $report): Response
    {
        $binary = $this->toOperationalUsageXlsx($report);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, scalar|null>>  $rows
     */
    public function toXlsx(array $headers, iterable $rows): string
    {
        $exportDirectory = storage_path('app/private/exports');
        File::ensureDirectoryExists($exportDirectory);

        $path = $exportDirectory.'/export-'.Str::uuid().'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationsXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($headers, $rows));
        $zip->close();

        $binary = File::get($path);
        File::delete($path);

        return $binary;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function toOperationalUsageXlsx(array $report): string
    {
        $exportDirectory = storage_path('app/private/exports');
        File::ensureDirectoryExists($exportDirectory);

        $path = $exportDirectory.'/operational-usage-'.Str::uuid().'.xlsx';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml(true));
        $zip->addFromString('_rels/.rels', $this->rootRelationsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationsXml(true));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->operationalWorksheetXml($report));
        $zip->close();

        $binary = File::get($path);
        File::delete($path);

        return $binary;
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, scalar|null>>  $rows
     */
    private function worksheetXml(array $headers, iterable $rows): string
    {
        $sheetRows = [];
        $sheetRows[] = $this->rowXml(1, $headers);
        $rowNumber = 2;

        foreach ($rows as $row) {
            $sheetRows[] = $this->rowXml($rowNumber, $row);
            $rowNumber++;
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'</worksheet>';
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function operationalWorksheetXml(array $report): string
    {
        $lastColumn = $this->columnLetter(70);
        $sheetRows = [];
        $merges = [
            'A1:'.$lastColumn.'1',
            'A2:'.$lastColumn.'2',
            'A4:A5',
            'B4:B5',
            'C4:C5',
            'D4:D5',
            'E4:E5',
        ];

        $sheetRows[] = $this->styledRowXml(1, [
            'REKAP REALISASI PEMAKAIAN BBM, PRODUKSI, DAN WORKING HOURS '.$report['year'],
        ], 1, 28);
        $sheetRows[] = $this->styledRowXml(2, [
            'Site: '.$report['site_name'].' | VHS: '.$report['storage_name'],
        ], 9, 22);
        $sheetRows[] = '<row r="3" ht="8" customHeight="1"/>';

        $groupHeaders = ['NO', 'KELOMPOK ALAT', 'UNIT', 'VHS', 'SATUAN PRODUKSI'];
        foreach ($report['months'] as $month => $monthName) {
            $startColumn = 6 + (($month - 1) * 5);
            $endColumn = $startColumn + 4;
            $groupHeaders[] = $monthName;
            $groupHeaders = [...$groupHeaders, null, null, null, null];
            $merges[] = $this->columnLetter($startColumn).'4:'.$this->columnLetter($endColumn).'4';
        }
        $groupHeaders[] = 'TOTAL SETAHUN';
        $groupHeaders = [...$groupHeaders, null, null, null, null];
        $merges[] = $this->columnLetter(66).'4:'.$lastColumn.'4';

        $sheetRows[] = $this->styledRowXml(4, $groupHeaders, 2, 24);

        $subHeaders = [null, null, null, null, null];
        foreach (range(1, 13) as $period) {
            $subHeaders = [
                ...$subHeaders,
                'Jumlah Liter',
                'Produksi',
                'Liter/Produksi',
                'Working Hours',
                'Liter/Jam',
            ];
        }
        $sheetRows[] = $this->styledRowXml(5, $subHeaders, 3, 42);

        $rowNumber = 6;
        $sequence = 1;
        foreach ($report['groups'] as $group) {
            $sheetRows[] = $this->styledRowXml($rowNumber, [$group['label']], 4, 22);
            $merges[] = 'A'.$rowNumber.':'.$lastColumn.$rowNumber;
            $rowNumber++;

            foreach ($group['rows'] as $row) {
                $values = [
                    $sequence,
                    $row['unit_type'],
                    $row['unit']->unit_code.' - '.$row['unit']->unit_name,
                    $row['storage']?->storage_name ?? '-',
                    $row['production_unit'] ?? '-',
                ];

                foreach ($row['months'] as $metrics) {
                    $values = [...$values, ...$this->operationalMetricValues($metrics)];
                }
                $values = [...$values, ...$this->operationalMetricValues($row['total'])];

                $styles = [5, 5, 5, 5, 5, ...array_fill(0, 65, 6)];
                $sheetRows[] = $this->styledRowXml($rowNumber, $values, $styles, 20);
                $rowNumber++;
                $sequence++;
            }

            $groupTotalValues = [null, null, 'JUMLAH '.$group['label'], null, null];
            foreach ($group['totals']['months'] as $metrics) {
                $groupTotalValues = [...$groupTotalValues, ...$this->operationalMetricValues($metrics)];
            }
            $groupTotalValues = [...$groupTotalValues, ...$this->operationalMetricValues($group['totals']['total'])];
            $groupStyles = [7, 7, 7, 7, 7, ...array_fill(0, 65, 8)];
            $sheetRows[] = $this->styledRowXml($rowNumber, $groupTotalValues, $groupStyles, 21);
            $rowNumber++;
        }

        $grandTotalValues = [null, null, 'TOTAL KESELURUHAN', null, null];
        foreach ($report['totals']['months'] as $metrics) {
            $grandTotalValues = [...$grandTotalValues, ...$this->operationalMetricValues($metrics)];
        }
        $grandTotalValues = [...$grandTotalValues, ...$this->operationalMetricValues($report['totals']['total'])];
        $grandStyles = [10, 10, 10, 10, 10, ...array_fill(0, 65, 11)];
        $sheetRows[] = $this->styledRowXml($rowNumber, $grandTotalValues, $grandStyles, 24);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane xSplit="5" ySplit="5" topLeftCell="F6" activePane="bottomRight" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'
            .'<col min="1" max="1" width="6" customWidth="1"/>'
            .'<col min="2" max="2" width="25" customWidth="1"/>'
            .'<col min="3" max="3" width="34" customWidth="1"/>'
            .'<col min="4" max="4" width="23" customWidth="1"/>'
            .'<col min="5" max="5" width="17" customWidth="1"/>'
            .'<col min="6" max="70" width="14" customWidth="1"/>'
            .'</cols>'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .'<mergeCells count="'.count($merges).'">'
            .implode('', array_map(fn (string $merge): string => '<mergeCell ref="'.$merge.'"/>', $merges))
            .'</mergeCells>'
            .'<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            .'<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            .'</worksheet>';
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<float|null>
     */
    private function operationalMetricValues(array $metrics): array
    {
        return [
            $metrics['refueling_count'] > 0 ? $metrics['fuel'] : null,
            $metrics['production'],
            $metrics['liter_per_production'],
            $metrics['working_hours'],
            $metrics['liter_per_hour'],
        ];
    }

    /**
     * @param  iterable<int, scalar|null>  $cells
     * @param  int|list<int>  $styles
     */
    private function styledRowXml(int $rowNumber, iterable $cells, int|array $styles, int $height): string
    {
        $xml = '<row r="'.$rowNumber.'" ht="'.$height.'" customHeight="1">';

        foreach (array_values(is_array($cells) ? $cells : iterator_to_array($cells)) as $index => $value) {
            $coordinate = $this->columnLetter($index + 1).$rowNumber;
            $style = is_array($styles) ? ($styles[$index] ?? 0) : $styles;

            if ($value === null || $value === '') {
                $xml .= '<c r="'.$coordinate.'" s="'.$style.'"/>';
            } elseif (is_int($value) || is_float($value)) {
                $xml .= '<c r="'.$coordinate.'" s="'.$style.'"><v>'.$value.'</v></c>';
            } else {
                $xml .= '<c r="'.$coordinate.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$this->escape((string) $value).'</t></is></c>';
            }
        }

        return $xml.'</row>';
    }

    /**
     * @param  iterable<int, scalar|null>  $cells
     */
    private function rowXml(int $rowNumber, iterable $cells): string
    {
        $xml = '<row r="'.$rowNumber.'">';

        foreach (array_values(is_array($cells) ? $cells : iterator_to_array($cells)) as $index => $value) {
            $coordinate = $this->columnLetter($index + 1).$rowNumber;
            $xml .= '<c r="'.$coordinate.'" t="inlineStr"><is><t>'.$this->escape((string) $value).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private function columnLetter(int $column): string
    {
        $letter = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letter = chr(65 + $remainder).$letter;
            $column = intdiv($column - 1, 26);
        }

        return $letter;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypesXml(bool $withStyles = false): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .($withStyles ? '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' : '')
            .'</Types>';
    }

    private function rootRelationsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationsXml(bool $withStyles = false): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .($withStyles ? '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' : '')
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.###"/></numFmts>'
            .'<fonts count="3">'
            .'<font><sz val="10"/><name val="Arial"/><family val="2"/></font>'
            .'<font><b/><sz val="14"/><name val="Arial"/><family val="2"/></font>'
            .'<font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Arial"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="5">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1F2E32"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDCEDEA"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE9EEF0"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border><left style="thin"><color rgb="FFB8C7CC"/></left><right style="thin"><color rgb="FFB8C7CC"/></right><top style="thin"><color rgb="FFB8C7CC"/></top><bottom style="thin"><color rgb="FFB8C7CC"/></bottom><diagonal/></border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="12">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="1" fillId="4" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="1" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="2" fillId="2" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
