<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class KsefSettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('settings.ksef', [
            'organization' => $request->user()->organization,
            'has_token'    => (bool) $request->user()->organization->ksef_token_encrypted,
            'has_cert'     => (bool) $request->user()->organization->ksef_cert_path,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ksef_mode'                => ['required', 'in:disabled,test,production'],
            'ksef_identifier'          => ['nullable', 'string', 'max:20'],
            'ksef_token'               => ['nullable', 'string', 'max:255'],
            'invoice_number_format'    => ['required', 'string', 'max:100'],
            'invoice_payment_due_days' => ['required', 'integer', 'min:0', 'max:120'],
            'cert_file'                => ['nullable', 'file', 'mimes:pem,crt,pfx,p12', 'max:2048'],
        ]);

        $org = $request->user()->organization;
        $update = [
            'ksef_mode'                => $data['ksef_mode'],
            'ksef_identifier'          => $data['ksef_identifier'] ?? null,
            'invoice_number_format'    => $data['invoice_number_format'],
            'invoice_payment_due_days' => (int) $data['invoice_payment_due_days'],
        ];

        if (! empty($data['ksef_token'])) {
            $update['ksef_token_encrypted'] = Crypt::encryptString($data['ksef_token']);
        }

        if ($request->hasFile('cert_file')) {
            $file = $request->file('cert_file');
            $path = sprintf('ksef/%d/cert.%s', $org->id, $file->getClientOriginalExtension());

            // Stary certyfikat — wyczyść.
            if ($org->ksef_cert_path) {
                Storage::disk('local')->delete($org->ksef_cert_path);
            }
            Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
            $update['ksef_cert_path'] = $path;
        }

        $org->update($update);

        return back()->with('success', 'Ustawienia KSeF zapisane.');
    }

    public function deleteCert(Request $request)
    {
        $org = $request->user()->organization;
        if ($org->ksef_cert_path) {
            Storage::disk('local')->delete($org->ksef_cert_path);
            $org->update(['ksef_cert_path' => null]);
        }
        return back()->with('success', 'Certyfikat KSeF usunięty.');
    }
}
