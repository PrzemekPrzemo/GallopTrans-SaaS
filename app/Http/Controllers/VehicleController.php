<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        return view('vehicles.index', [
            'vehicles' => Vehicle::orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('vehicles.form', ['vehicle' => new Vehicle(['is_active' => true, 'fuel_type' => 'diesel', 'fuel_consumption' => 25])]);
    }

    public function store(Request $request)
    {
        Vehicle::create($this->validated($request));
        return redirect()->route('vehicles.index')->with('success', 'Pojazd dodany.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.form', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $vehicle->update($this->validated($request));
        return redirect()->route('vehicles.index')->with('success', 'Pojazd zaktualizowany.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Pojazd usunięty.');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'name'             => ['required', 'string', 'max:190'],
            'plate'            => ['nullable', 'string', 'max:20'],
            'fuel_type'        => ['required', 'in:diesel,petrol,lpg,electric'],
            'fuel_consumption' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'horse_capacity'   => ['required', 'integer', 'min:1', 'max:50'],
            'max_weight_kg'    => ['nullable', 'integer', 'min:0', 'max:50000'],
            'height_m'         => ['nullable', 'numeric', 'min:0', 'max:9.99'],
            'length_m'         => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'width_m'          => ['nullable', 'numeric', 'min:0', 'max:9.99'],
            'axles'            => ['nullable', 'integer', 'min:2', 'max:10'],
            'is_trailer'       => ['sometimes', 'boolean'],
            'is_default'       => ['sometimes', 'boolean'],
            'is_active'        => ['sometimes', 'boolean'],
            'notes'            => ['nullable', 'string'],
        ]);
    }
}
