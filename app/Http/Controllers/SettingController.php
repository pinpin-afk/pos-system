<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingRequest;
use App\Models\StoreSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Settings/Edit', [
            'setting' => StoreSetting::current(),
        ]);
    }

    public function update(UpdateSettingRequest $request): RedirectResponse
    {
        $setting = StoreSetting::current();
        $data = $request->safe()->except(['logo']);

        if ($request->hasFile('logo')) {
            if ($setting->logo) {
                Storage::disk('public')->delete($setting->logo);
            }

            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $setting->update($data);

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
