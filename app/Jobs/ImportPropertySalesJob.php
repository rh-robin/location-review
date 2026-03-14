<?php

namespace App\Jobs;

use App\Models\PropertySaleImport;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportPropertySalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 0;

    protected PropertySaleImport $import;

    public function __construct(PropertySaleImport $import)
    {
        $this->import = $import;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1024M');

        Log::info("Import started", ['import_id' => $this->import->id]);

        $this->import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {

            $filePath = Storage::disk('local')->path($this->import->file_name);

            Log::info("File path resolved", ['path' => $filePath]);

            if (!file_exists($filePath)) {
                throw new \Exception("Uploaded file not found at: " . $filePath);
            }

            $escapedPath = addslashes($filePath);

            Log::info("Running LOAD DATA");

            DB::statement("
                LOAD DATA LOCAL INFILE '{$escapedPath}'
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

            Log::info("LOAD DATA completed");

            $result = DB::select("SELECT ROW_COUNT() as count");
            $inserted = $result[0]->count ?? 0;

            Log::info("Rows inserted", ['count' => $inserted]);

            $this->import->update([
                'status' => 'completed',
                'inserted_rows' => $inserted,
                'completed_at' => now(),
            ]);

            Log::info("Import marked completed");

            Storage::disk('local')->delete($this->import->file_name);

            Log::info("File deleted");

        } catch (\Throwable $e) {

            Log::error("Import failed", [
                'import_id' => $this->import->id,
                'error' => $e->getMessage()
            ]);

            $this->import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
}
