<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        // Pogrupowane ustawienia tej organizacji.
        $byGroup = Setting::orderBy('group')->orderBy('id')->get()->groupBy('group');

        return view('settings.edit', [
            'byGroup' => $byGroup,
            'organization' => $request->user()->organization,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string'],
            'organization' => ['array'],
        ]);

        foreach ($data['settings'] as $key => $value) {
            SettingsService::set((string) $key, (string) $value);
        }

        $orgPayload = $request->input('organization', []);
        if ($orgPayload && is_array($orgPayload)) {
            $request->user()->organization->update(array_intersect_key($orgPayload, array_flip([
                'name', 'company_address', 'company_nip', 'company_phone', 'company_email', 'company_bank',
            ])));
        }

        return back()->with('success', 'Ustawienia zapisane.');
    }
}
