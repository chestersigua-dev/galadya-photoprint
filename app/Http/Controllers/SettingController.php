<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Display settings page.
     */
    public function index(): View
    {
        $settings = Setting::all()->pluck('value', 'key');

        return view('settings.index', compact('settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $keys = [
            'studio_name',
            'studio_tagline',
            'studio_contact',
            'studio_email',
            'studio_address',
            'currency_symbol',
            'print_margin_px',
            'print_dpi',
            'max_selection_limit',
            'receipt_footer_text',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }

        // Handle logo upload if provided
        if ($request->hasFile('studio_logo')) {
            $file = $request->file('studio_logo');
            $file->move(public_path('images'), 'galadya-logo.png');
        }

        return redirect()->route('settings.index')
            ->with('success', 'Studio configuration updated successfully.');
    }
}

