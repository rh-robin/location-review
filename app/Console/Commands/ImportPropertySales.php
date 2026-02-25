<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertySale;
use Illuminate\Support\Facades\DB;

class ImportPropertySales extends Command
{
    protected $signature = 'property:import {file}';
    protected $description = 'Import UK Property Sales CSV';

    public function handle()
    {
        ini_set('memory_limit', '1024M');
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found.");
            return;
        }

        $handle = fopen($filePath, 'r');

        DB::disableQueryLog();

        $batch = [];
        $batchSize = 2000;

        while (($row = fgetcsv($handle)) !== false) {

            if (count($row) < 16) continue;

            if ($row[15] === 'D') continue;
            if ($row[14] !== 'A') continue;

            if (empty($row[1]) || empty($row[2]) || empty($row[4]) || empty($row[6])) {
                continue;
            }

            $postcode = strtoupper(trim($row[3]));

            $postcodeDistrict = null;
            $postcodeSector = null;

            if (!empty($postcode) && str_contains($postcode, ' ')) {
                $parts = explode(' ', $postcode);

                if (count($parts) === 2) {
                    $postcodeDistrict = trim($parts[0]);
                    $postcodeSector = trim($parts[0]) . ' ' . substr(trim($parts[1]), 0, 1);
                }
            }

            if (!$postcodeDistrict || !$postcodeSector) {
                continue;
            }

            $timestamp = strtotime($row[2]);
            if (!$timestamp) continue;

            $transferDate = date('Y-m-d', $timestamp);
            $year = date('Y', $timestamp);

            $batch[] = [
                'transaction_id' => trim($row[0]),
                'price' => (int) $row[1],
                'transfer_date' => $transferDate,
                'year' => $year,
                'postcode' => $postcode,
                'postcode_district' => $postcodeDistrict,
                'postcode_sector' => $postcodeSector,
                'property_type' => trim($row[4]),
                'new_build' => trim($row[5]),
                'duration' => trim($row[6]),
                'town' => $row[11] ?? null,
                'district' => $row[12] ?? null,
                'county' => $row[13] ?? null,
                'ppd_category' => $row[14],
                'record_status' => $row[15],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                DB::table('property_sales')->insertOrIgnore($batch);
                $batch = [];
            }
        }


        if (!empty($batch)) {
            DB::table('property_sales')->insertOrIgnore($batch);
        }

        fclose($handle);

        $this->info('Import completed successfully.');
    }
}
