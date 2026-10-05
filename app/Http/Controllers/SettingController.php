<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('family-100.settings', ['timerDuration' => Setting::timerDuration()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'timer_duration' => ['required', 'integer', 'min:5', 'max:3600'],
        ]);

        Setting::put(Setting::TIMER_DURATION, $data['timer_duration']);

        return redirect()
            ->route('family-100.settings.edit')
            ->with('status', 'Pengaturan disimpan.');
    }
}
