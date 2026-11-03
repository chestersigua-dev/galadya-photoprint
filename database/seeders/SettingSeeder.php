<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'studio_name' => 'Galadya Photoprint',
            'studio_tagline' => 'Self-Shoot Studio & High-Definition Printing',
            'studio_contact' => '+63 917 123 4567',
            'studio_email' => 'hello@galadyastudio.ph',
            'studio_address' => 'Unit 302, Lifestyle Building, Quezon City, Philippines',
            'currency_symbol' => '₱',
            'print_margin_px' => '10',
            'print_dpi' => '300',
            'print_canvas_width' => '1200',
            'print_canvas_height' => '1800',
            'max_selection_limit' => '10',
            'receipt_footer_text' => 'Thank you for capturing your special moments with Galadya Studio! Tag us on IG @galadyaph',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
        }
    }
}

