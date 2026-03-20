<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SettingTime;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingTimeController extends Controller
{
    public function edit()
    {
        return Inertia::render('Admin/SettingTimes/Edit', [
            'shuffleIntervalSeconds' => (int) SettingTime::getValue('shuffle_interval_seconds', 10),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'shuffle_interval_seconds' => ['required', 'integer', 'min:1'],
        ]);

        SettingTime::setValue('shuffle_interval_seconds', $validated['shuffle_interval_seconds']);

        return redirect()->route('admin.setting-times.edit');
    }
}