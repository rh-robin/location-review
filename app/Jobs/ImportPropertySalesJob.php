<?php

namespace App\Jobs;

use App\Models\PropertySaleImport;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportPropertySalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected PropertySaleImport $import;

    public function __construct(PropertySaleImport $import)
    {
        $this->import = $import;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1024M');

        $this->import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {

            $originalPath = storage_path('app/private/' . $this->import->file_name);

            if (!file_exists($originalPath)) {
                throw new \Exception("Uploaded file not found at: " . $originalPath);
            }

            // MySQL secure folder (from SHOW VARIABLES LIKE 'secure_file_priv')
            $mysqlImportPath = 'C:/ProgramData/MySQL/MySQL Server 8.0/Uploads/';

            if (!file_exists($mysqlImportPath)) {
                throw new \Exception("MySQL upload directory not found.");
            }

            $fileName = basename($originalPath);
            $newPath = $mysqlImportPath . $fileName;

            copy($originalPath, $newPath);
            chmod($newPath, 0777);

            $escapedPath = addslashes($newPath);

            $beforeCount = DB::table('property_sales')->count();

            DB::statement("
            LOAD DATA INFILE '{$escapedPath}'
            INTO TABLE property_sales
            FIELDS TERMINATED BY ','
            ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            (
                transaction_id,
                price,
                @transfer_date,
                postcode,
                property_type,
                new_build,
                duration,
                @paon,
                @saon,
                @street,
                @locality,
                town,
                district,
                county,
                ppd_category,
                record_status
            )
            SET
                transfer_date = STR_TO_DATE(@transfer_date, '%Y-%m-%d %H:%i'),
                year = YEAR(STR_TO_DATE(@transfer_date, '%Y-%m-%d %H:%i')),
                postcode = UPPER(TRIM(postcode)),
                postcode_district = SUBSTRING_INDEX(postcode, ' ', 1),
                postcode_sector = CONCAT(
                    SUBSTRING_INDEX(postcode, ' ', 1),
                    ' ',
                    LEFT(SUBSTRING_INDEX(postcode, ' ', -1), 1)
                ),
                created_at = NOW(),
                updated_at = NOW()
        ");

            $afterCount = DB::table('property_sales')->count();
            $inserted = $afterCount - $beforeCount;

            unlink($newPath);

            $this->import->update([
                'status' => 'completed',
                'inserted_rows' => $inserted,
                'completed_at' => now(),
            ]);

        } catch (\Throwable $e) {

            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
