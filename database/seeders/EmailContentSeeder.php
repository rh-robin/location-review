<?php

namespace Database\Seeders;

use App\Models\EmailContent;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmailContent::updateOrCreate(
            ['id' => 1], // Ensure only one row with id 1 is managed
            [
                'company_name' => 'Betacompass',
                'company_location' => 'UK',
            ]
        );
    }
}
