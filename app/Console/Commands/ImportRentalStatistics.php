<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RentalStatistic;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ImportRentalStatistics extends Command
{
    protected $signature = 'rental:import {file}';
    protected $description = 'Import Rental Statistics XLS file';

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error('File not found.');
            return;
        }

        $this->info('Loading XLS file (chunk mode)...');

        DB::disableQueryLog();

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $chunkSize = 500;
        $startRow = 4;

        $filter = new ChunkReadFilter();
        $reader->setReadFilter($filter);

        $insertedCount = 0;

        while (true) {

            $filter->setRows($startRow, $chunkSize);

            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getSheetByName('Table 1');

            if (!$sheet) {
                $this->error('Sheet "Table 1" not found.');
                return;
            }

            $rowsProcessed = 0;
            $batch = [];

            foreach ($sheet->getRowIterator($startRow, $startRow + $chunkSize - 1) as $row) {

                $rowIndex = $row->getRowIndex();

                $timePeriod = trim((string)$sheet->getCell("A$rowIndex")->getValue());
                $areaCode   = trim((string)$sheet->getCell("B$rowIndex")->getValue());
                $areaName   = trim((string)$sheet->getCell("C$rowIndex")->getValue());
                $region     = trim((string)$sheet->getCell("D$rowIndex")->getValue());

                if (!$timePeriod || !$areaName) {
                    continue;
                }

                try {

                    if (is_numeric($timePeriod)) {
                        $date = Carbon::instance(
                            ExcelDate::excelToDateTimeObject($timePeriod)
                        );
                    } else {
                        $date = Carbon::parse($timePeriod);
                    }

                } catch (\Exception $e) {
                    Log::warning("Date parse failed on row $rowIndex: " . $timePeriod);
                    continue;
                }

                if ($date->year < 2024) {
                    continue;
                }

                $rent1 = $this->cleanNumber($sheet->getCell("L$rowIndex")->getValue());
                $rent2 = $this->cleanNumber($sheet->getCell("P$rowIndex")->getValue());
                $rent3 = $this->cleanNumber($sheet->getCell("T$rowIndex")->getValue());
                $rent4 = $this->cleanNumber($sheet->getCell("X$rowIndex")->getValue());

                $rentDetached     = $this->cleanNumber($sheet->getCell("AB$rowIndex")->getValue());
                $rentSemiDetached = $this->cleanNumber($sheet->getCell("AF$rowIndex")->getValue());
                $rentTerraced     = $this->cleanNumber($sheet->getCell("AJ$rowIndex")->getValue());
                $rentFlat         = $this->cleanNumber($sheet->getCell("AN$rowIndex")->getValue());

                $batch[] = [
                    'area_code' => $areaCode,
                    'area_name' => $areaName,
                    'region' => $region,
                    'period_date' => $date->format('Y-m-01'),
                    'year' => $date->year,
                    'month' => $date->month,
                    'rent_1_bed' => $rent1,
                    'rent_2_bed' => $rent2,
                    'rent_3_bed' => $rent3,
                    'rent_4plus_bed' => $rent4,
                    'rent_detached' => $rentDetached,
                    'rent_semidetached' => $rentSemiDetached,
                    'rent_terraced' => $rentTerraced,
                    'rent_flat' => $rentFlat,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $rowsProcessed++;
            }

            if (!empty($batch)) {
                RentalStatistic::insert($batch);
                $insertedCount += count($batch);
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($rowsProcessed == 0) {
                break;
            }

            $startRow += $chunkSize;

            $this->info("Processed up to row $startRow");
        }

        $this->info("Import completed successfully. Rows inserted: $insertedCount");

        Log::info("Rental import completed", [
            'inserted_rows' => $insertedCount
        ]);
    }

    protected function cleanNumber($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '[X]' || $value === '-') {
            return null;
        }

        $value = str_replace(',', '', $value);

        if (!is_numeric($value)) {
            return null;
        }

        $number = (int) round($value);

        if ($number < 0 || $number > 100000) {
            return null;
        }

        return $number;
    }
}
