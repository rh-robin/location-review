<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class SalePriceEstimatorService
{
    protected int $minimumComparables = 15;
    protected int $cacheSeconds = 86400; // 24 hours

    public function estimate(array $data): array
    {
        $months = $data['months'] ?? 6;

        // Build cache key
        $cacheKey = sprintf(
            'sale_estimate:%s:%s:%s:%s',
            $data['postcode_sector'],
            $data['property_type'],
            $data['duration'],
            $months
        );

        return Cache::remember($cacheKey, $this->cacheSeconds, function () use ($data, $months) {

            $fromDate = Carbon::now()->subMonths($months)->startOfDay();

            $filters = [
                'property_type' => $data['property_type'],
                'duration'      => $data['duration'],
            ];

            // Try Sector
            $prices = $this->getPrices(
                ['postcode_sector' => $data['postcode_sector']],
                $filters,
                $fromDate
            );

            $level = 'sector';

            if (count($prices) < $this->minimumComparables) {

                // Try District
                $prices = $this->getPrices(
                    ['district' => $data['district']],
                    $filters,
                    $fromDate
                );

                $level = 'district';
            }

            if (count($prices) < $this->minimumComparables) {

                // Try County
                $prices = $this->getPrices(
                    ['county' => $data['county']],
                    $filters,
                    $fromDate
                );

                $level = 'county';
            }

            if (empty($prices)) {
                return [
                    'estimated_price' => null,
                    'confidence'      => 0,
                    'comparables'     => 0,
                    'level_used'      => null,
                ];
            }

            // Remove outliers
            $filteredPrices = $this->removeOutliers($prices);

            sort($filteredPrices);

            $median = $this->median($filteredPrices);

            // NEW: tighter range around median (-5% to +20%)
            $lowerBound = $median * 0.95;
            $upperBound = $median * 1.20;

            // Filter again based on median band
            $finalPrices = array_values(array_filter($filteredPrices, function ($price) use ($lowerBound, $upperBound) {
                return $price >= $lowerBound && $price <= $upperBound;
            }));

            // Safety fallback
            if (count($finalPrices) < 5) {
                // Relax the band instead of discarding it
                $lowerBound = $median * 0.80;
                $upperBound = $median * 1.25;

                $finalPrices = array_values(array_filter($filteredPrices, function ($price) use ($lowerBound, $upperBound) {
                    return $price >= $lowerBound && $price <= $upperBound;
                }));
            }

            return [
                'estimated_price' => $median,
                'min_price'       => min($finalPrices),
                'max_price'       => max($finalPrices),
                'comparables'     => count($finalPrices),
                'confidence'      => $this->confidence(count($finalPrices)),
                'level_used'      => $level,
            ];
        });
    }

    protected function getPrices(array $location, array $filters, $fromDate): array
    {
        return DB::table('property_sales')
            ->where($location)
            ->where($filters)
            ->where('transfer_date', '>=', $fromDate)
            ->pluck('price')
            ->toArray();
    }

    protected function median(array $prices): float
    {
        $count = count($prices);
        $middle = floor($count / 2);

        if ($count % 2) {
            return $prices[$middle];
        }

        return ($prices[$middle - 1] + $prices[$middle]) / 2;
    }

    protected function confidence(int $count): int
    {
        if ($count >= 30) return 95;
        if ($count >= 15) return 80;
        if ($count >= 8)  return 65;
        return 40;
    }


    protected function removeOutliers(array $prices): array
    {
        sort($prices);

        $count = count($prices);

        // If too few data points, don't filter
        if ($count < 10) {
            return $prices;
        }

        $q1 = $prices[(int) floor($count * 0.25)];
        $q3 = $prices[(int) floor($count * 0.75)];

        $iqr = $q3 - $q1;

        $lower = $q1 - (1.5 * $iqr);
        $upper = $q3 + (1.5 * $iqr);

        $filtered = array_values(array_filter($prices, function ($price) use ($lower, $upper) {
            return $price >= $lower && $price <= $upper;
        }));

        // Safety fallback (important)
        if (count($filtered) < 5) {
            return $prices;
        }

        return $filtered;
    }
}
