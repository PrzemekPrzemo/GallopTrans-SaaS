<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Setting;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Onboarding po rejestracji: zakładamy organizację, ustawiamy seedy
 * (settings + domyślny pojazd) i 14-dniowy trial.
 */
class OnboardingController extends Controller
{
    public function create(Request $request)
    {
        if ($request->user()->organization_id) {
            return redirect()->route('dashboard');
        }
        return view('onboarding.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name'    => ['required', 'string', 'max:190'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_nip'     => ['nullable', 'string', 'max:20'],
            'company_phone'   => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $org = Organization::create([
                'name'            => $validated['company_name'],
                'company_address' => $validated['company_address'] ?? null,
                'company_nip'     => $validated['company_nip'] ?? null,
                'company_phone'   => $validated['company_phone'] ?? null,
                'company_email'   => $request->user()->email,
                'plan'            => 'trial',
                'trial_ends_at'   => now()->addDays(14),
            ]);

            $user = $request->user();
            $user->organization_id = $org->id;
            $user->role = 'owner';
            $user->save();

            $this->seedDefaultSettings($org->id);
            $this->seedDefaultVehicle($org->id);
        });

        return redirect()->route('dashboard')->with('success', 'Konto firmowe utworzone — masz 14 dni trialu.');
    }

    private function seedDefaultSettings(int $orgId): void
    {
        $defaults = [
            ['key' => 'base_rate_per_km',     'value' => '4.50',  'type' => 'float',  'group' => 'pricing', 'label' => 'Stawka podstawowa (waluta/km)'],
            ['key' => 'surcharge_percent',    'value' => '15.00', 'type' => 'float',  'group' => 'pricing', 'label' => 'Narzut % (marża)'],
            ['key' => 'extra_horse_fee',      'value' => '150.00','type' => 'float',  'group' => 'pricing', 'label' => 'Dopłata za każdego kolejnego konia'],
            ['key' => 'fixed_fees',           'value' => '0.00',  'type' => 'float',  'group' => 'pricing', 'label' => 'Opłaty stałe'],
            ['key' => 'min_quote_amount',     'value' => '500.00','type' => 'float',  'group' => 'pricing', 'label' => 'Minimalna kwota oferty (netto)'],
            ['key' => 'stay_24h_cost',        'value' => '200.00','type' => 'float',  'group' => 'pricing', 'label' => 'Koszt postoju 24h'],
            ['key' => 'round_trip_default',   'value' => '1',     'type' => 'bool',   'group' => 'pricing', 'label' => 'Domyślnie w obie strony'],
            ['key' => 'vat_percent',          'value' => '23.00', 'type' => 'float',  'group' => 'pricing', 'label' => 'VAT %'],
            ['key' => 'quote_validity_days',  'value' => '14',    'type' => 'int',    'group' => 'pricing', 'label' => 'Ważność oferty (dni)'],
            ['key' => 'default_currency',     'value' => 'PLN',   'type' => 'string', 'group' => 'pricing', 'label' => 'Domyślna waluta'],
            ['key' => 'eur_exchange_rate',    'value' => '4.30',  'type' => 'float',  'group' => 'pricing', 'label' => 'Kurs EUR/PLN fallback'],
            ['key' => 'ors_profile',          'value' => 'driving-hgv', 'type' => 'string', 'group' => 'routing', 'label' => 'Profil ORS'],
            ['key' => 'ors_vehicle_weight',   'value' => '7500',  'type' => 'int',    'group' => 'routing', 'label' => 'Masa pojazdu (kg)'],
            ['key' => 'ors_vehicle_height',   'value' => '3.5',   'type' => 'float',  'group' => 'routing', 'label' => 'Wysokość pojazdu (m)'],
            ['key' => 'toll_hgv_threshold_kg','value' => '3500',  'type' => 'int',    'group' => 'routing', 'label' => 'Próg HGV (kg)'],
            ['key' => 'toll_rate_light',      'value' => '0.20',  'type' => 'float',  'group' => 'routing', 'label' => 'Stawka opłat / km (lekki)'],
            ['key' => 'toll_rate_hgv',        'value' => '0.55',  'type' => 'float',  'group' => 'routing', 'label' => 'Stawka opłat / km (HGV)'],

            ['key' => 'hero_title',     'value' => 'Profesjonalny transport koni',                                          'type' => 'string', 'group' => 'public_website', 'label' => 'Hero — nagłówek'],
            ['key' => 'hero_subtitle',  'value' => 'Bezpieczny, komfortowy transport koni w Polsce i Europie. Wyceń trasę online.', 'type' => 'string', 'group' => 'public_website', 'label' => 'Hero — opis'],
            ['key' => 'services_text',  'value' => '',                                                                       'type' => 'string', 'group' => 'public_website', 'label' => 'Sekcja "O nas / Usługi"'],
            ['key' => 'contact_text',   'value' => '',                                                                       'type' => 'string', 'group' => 'public_website', 'label' => 'Sekcja kontakt'],
        ];

        foreach ($defaults as $row) {
            Setting::create(array_merge(['organization_id' => $orgId], $row));
        }
    }

    private function seedDefaultVehicle(int $orgId): void
    {
        Vehicle::create([
            'organization_id'  => $orgId,
            'name'             => 'Domyślny zestaw',
            'fuel_type'        => 'diesel',
            'fuel_consumption' => 25.00,
            'horse_capacity'   => 4,
            'max_weight_kg'    => 7500,
            'height_m'         => 3.5,
            'is_default'       => true,
            'is_active'        => true,
        ]);
    }
}
