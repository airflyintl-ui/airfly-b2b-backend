<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSettingsController extends Controller
{
    // GET ADMIN SETTINGS
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'System settings retrieved successfully.',
            'settings' => [
                'exchange_rate' => SystemSetting::getValue('exchange_rate'),
                'service_charge' => SystemSetting::getValue('service_charge'),
            ],
        ]);
    }

    // UPDATE ADMIN SETTINGS
    public function update(Request $request)
    {
        $validated = $request->validate([
            'exchange_rate' => ['required', 'numeric', 'min:0.0001'],
            'service_charge' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {

            SystemSetting::setValue(
                'exchange_rate',
                $validated['exchange_rate']
            );

            SystemSetting::setValue(
                'service_charge',
                $validated['service_charge']
            );

        });

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully.',
            'settings' => [
                'exchange_rate' => SystemSetting::getValue('exchange_rate'),
                'service_charge' => SystemSetting::getValue('service_charge'),
            ],
        ]);
    }
}
