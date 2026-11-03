<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\PackageAddon;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'title' => 'Solo Self Shoot',
                'base_price' => 299.00,
                'description' => '15-minute solo studio session with professional lighting, wireless remote, and dual 4R photostrip + solo 4R print.',
                'is_active' => true,
            ],
            [
                'title' => 'Duo Self Shoot',
                'base_price' => 399.00,
                'description' => '20-minute couples/duo session with background choices, props, and dual 4R photostrip + solo 4R print.',
                'is_active' => true,
            ],
            [
                'title' => 'Trio Self Shoot',
                'base_price' => 499.00,
                'description' => '25-minute 3-person studio session with backdrop choices, fun accessories, and high-res print outputs.',
                'is_active' => true,
            ],
            [
                'title' => 'Group Self Shoot',
                'base_price' => 599.00,
                'description' => '30-minute squad/family session (up to 5 pax) with unlimited captures, full lighting, and 4R prints.',
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::firstOrCreate(
                ['title' => $pkg['title']],
                $pkg
            );
        }

        $addons = [
            [
                'title' => 'Photographer',
                'price' => 300.00,
                'is_active' => true,
            ],
            [
                'title' => 'Extra 15 Minutes',
                'price' => 100.00,
                'is_active' => true,
            ],
            [
                'title' => 'Extra Pax',
                'price' => 100.00,
                'is_active' => true,
            ],
            [
                'title' => 'Extra Pet',
                'price' => 100.00,
                'is_active' => true,
            ],
        ];

        foreach ($addons as $addon) {
            PackageAddon::firstOrCreate(
                ['title' => $addon['title']],
                $addon
            );
        }
    }
}

