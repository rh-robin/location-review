<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Report;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Number of registered users')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
                    Stat::make('Total Reports', Report::count())
                ->description('Total Number of Reports')
                ->descriptionIcon('heroicon-o-flag')
                ->color('primary'),
            Stat::make('Total Reviews', Review::count())
                ->description('Total Number of Reviews and Ratings')
                ->descriptionIcon('heroicon-o-star')
                ->color('success'),
            Stat::make('Average Review', number_format(Review::avg('rating') ?? 0, 1))
                ->description('Average per review')
                ->descriptionIcon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
