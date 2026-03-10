<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RentalStatistic;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportRentalStatistics extends Command
{
    protected $signature = 'rental:import {file}';
    protected $description = 'Import Rental Statistics XLS file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error('File not found.');
            return;
        }

        $this->info('Loading XLS file (optimized mode)...');

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $sheet = $spreadsheet->getSheetByName('Table 1');

        if (!$sheet) {
            $this->error('Sheet "Table 1" not found.');
            return;
        }

        DB::disableQueryLog();

        $highestRow = $sheet->getHighestRow();

        $batch = [];
        $batchSize = 500;
        $insertedCount = 0;
        $debugCounter = 0;

        for ($row = 4; $row <= $highestRow; $row++) {

            $timePeriod = trim((string) $sheet->getCell("A$row")->getValue());
            $areaCode   = trim((string) $sheet->getCell("B$row")->getValue());
            $areaName   = trim((string) $sheet->getCell("C$row")->getValue());
            $region     = trim((string) $sheet->getCell("D$row")->getValue());

            if (!$timePeriod || !$areaName) {
                continue;
            }

            // Debug first few rows
            if ($debugCounter < 5) {
                Log::info("Row $row", [
                    'timePeriod' => $timePeriod,
                    'areaCode' => $areaCode,
                    'areaName' => $areaName,
                ]);
                $debugCounter++;
            }

            // Parse date safely
            try {

                if (is_numeric($timePeriod)) {
                    // Excel serial date
                    $date = Carbon::instance(
                        ExcelDate::excelToDateTimeObject($timePeriod)
                    );
                } else {
                    // Text date like Jan-2015
                    $date = Carbon::parse($timePeriod);
                }

            } catch (\Exception $e) {
                Log::warning("Date parse failed on row $row: " . $timePeriod);
                continue;
            }

            // Import only 2024+
            if ($date->year < 2024) {
                continue;
            }

            // Clean numeric values
            $rent1 = $this->cleanNumber($sheet->getCell("L$row")->getValue());
            $rent2 = $this->cleanNumber($sheet->getCell("P$row")->getValue());
            $rent3 = $this->cleanNumber($sheet->getCell("T$row")->getValue());
            $rent4 = $this->cleanNumber($sheet->getCell("X$row")->getValue());

            $rentDetached     = $this->cleanNumber($sheet->getCell("AB$row")->getValue());
            $rentSemiDetached = $this->cleanNumber($sheet->getCell("AF$row")->getValue());
            $rentTerraced     = $this->cleanNumber($sheet->getCell("AJ$row")->getValue());
            $rentFlat         = $this->cleanNumber($sheet->getCell("AN$row")->getValue());

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

            if (count($batch) >= $batchSize) {
                RentalStatistic::insert($batch);
                $insertedCount += count($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            RentalStatistic::insert($batch);
            $insertedCount += count($batch);
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

        // Remove commas
        $value = str_replace(',', '', $value);

        // If not numeric, skip
        if (!is_numeric($value)) {
            return null;
        }

        $number = (int) round($value);

        // Safety check: prevent extreme values
        if ($number < 0 || $number > 100000) {
            return null;
        }

        return $number;
    }
}
