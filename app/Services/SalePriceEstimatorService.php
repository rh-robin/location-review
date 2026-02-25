<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalePriceEstimatorService
{
    protected int $minimumComparables = 15;

    public function estimate(array $data): array
    {
        $months = $data['months'] ?? 6;

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
                'confidence' => 0,
                'comparables' => 0,
                'level_used' => null,
            ];
        }

        sort($prices);

        $median = $this->median($prices);

        return [
            'estimated_price' => $median,
            'min_price'       => min($prices),
            'max_price'       => max($prices),
            'comparables'     => count($prices),
            'confidence'      => $this->confidence(count($prices)),
            'level_used'      => $level,
        ];
    }

    protected function getPrices(array $location, array $filters, $fromDate): array
    {
        $query = DB::table('property_sales')
            ->where($location)
            ->where($filters)
            ->where('transfer_date', '>=', $fromDate)
            ->pluck('price');

        return $query->toArray();
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
}
