<?php

namespace App\Services;

use App\Models\RentalStatistic;
use Illuminate\Support\Facades\Cache;

class RentalEstimatorService
{
    protected int $cacheSeconds = 86400; // 24 hours

    public function estimate(array $data): array
    {
        $postcodeSector = $data['postcode_sector'];
        $district = $data['district'];
        $bedrooms = (int) $data['bedrooms'];

        $bedrooms = $bedrooms >= 4 ? 4 : $bedrooms;

        $monthsWindow = config('rent.months_window', 5);

        $cacheKey = sprintf(
            'rent_estimate:%s:%s',
            $postcodeSector,
            $bedrooms
        );

        return Cache::remember($cacheKey, $this->cacheSeconds, function () use (
            $district,
            $bedrooms,
            $monthsWindow
        ) {

            // 1️⃣ Try Local Authority
            $rows = $this->getLatestRows('area_name', $district, $monthsWindow);

            $fallbackUsed = false;
            $areaUsed = $district;

            // 2️⃣ If district not found, determine region from rental table
            if ($rows->isEmpty()) {

                $region = RentalStatistic::whereRaw(
                    "LOWER(area_name) = ?",
                    [strtolower($district)]
                )->value('region');

                if ($region) {
                    $rows = $this->getLatestRows('region', $region, $monthsWindow);
                    $fallbackUsed = true;
                    $areaUsed = $region;
                }
            }

            if ($rows->isEmpty()) {
                return [
                    'estimated_rent' => null,
                    'min_range' => null,
                    'max_range' => null,
                    'area_used' => null,
                    'fallback_used' => false,
                    'months_used' => 0,
                ];
            }

            $column = $this->bedroomColumn($bedrooms);

            $values = $rows->pluck($column)->filter()->values();

            if ($values->isEmpty()) {
                return [
                    'estimated_rent' => null,
                    'min_range' => null,
                    'max_range' => null,
                    'area_used' => $areaUsed,
                    'fallback_used' => $fallbackUsed,
                    'months_used' => 0,
                ];
            }

            $average = (int) round($values->avg());

            $monthsUsed = $values->count();

            return [
                'estimated_rent' => $average,
                'min_range' => (int) round($average * 0.95),
                'max_range' => (int) round($average * 1.05),
                //'confidence' => $this->confidenceScore($monthsUsed),
                'area_used' => $areaUsed,
                'fallback_used' => $fallbackUsed,
                'months_used' => $monthsUsed,
            ];
        });
    }

    protected function getLatestRows(string $field, string $value, int $limit)
    {
        return RentalStatistic::whereRaw("LOWER($field) = ?", [strtolower($value)])
            ->orderByDesc('period_date')
            ->limit($limit)
            ->get();
    }

    protected function bedroomColumn(int $bedrooms): string
    {
        return match ($bedrooms) {
            1 => 'rent_1_bed',
            2 => 'rent_2_bed',
            3 => 'rent_3_bed',
            default => 'rent_4plus_bed',
        };
    }


    protected function confidenceScore(int $months): int
    {
        if ($months >= 5) {
            return 95;
        }

        if ($months >= 4) {
            return 90;
        }

        if ($months >= 3) {
            return 80;
        }

        if ($months >= 2) {
            return 70;
        }

        return 60;
    }
}
