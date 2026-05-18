<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::query()
            ->withCount('quotes')
            ->withSum(['quotes as accepted_gross' => fn ($q) => $q->where('status', 'accepted')], 'total_gross')
            ->orderBy('name')
            ->paginate(30);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.form', ['client' => new Client()]);
    }

    public function store(Request $request)
    {
        $client = Client::create($this->validated($request));
        return redirect()->route('clients.show', $client)->with('success', 'Klient dodany.');
    }

    public function show(Client $client)
    {
        $client->load(['quotes' => fn ($q) => $q->orderByDesc('id')->limit(50)]);
        return view('clients.show', compact('client'));
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validated($request));
        return redirect()->route('clients.show', $client)->with('success', 'Klient zaktualizowany.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Klient usunięty.');
    }

    /** Autocomplete dla kalkulatora — wpisuje frazę, dostaje listę matching. */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q'));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = Client::query()
            ->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('company', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('nip', 'like', "%{$q}%");
            })
            ->limit(8)
            ->get(['id', 'name', 'email', 'phone', 'company', 'nip', 'address',
                   'default_rate_per_km', 'default_min_amount']);

        return response()->json(['results' => $results]);
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'name'                => ['required', 'string', 'max:190'],
            'email'               => ['nullable', 'email', 'max:190'],
            'phone'               => ['nullable', 'string', 'max:40'],
            'company'             => ['nullable', 'string', 'max:190'],
            'nip'                 => ['nullable', 'string', 'max:20'],
            'address'             => ['nullable', 'string', 'max:255'],
            'default_rate_per_km' => ['nullable', 'numeric', 'min:0'],
            'default_min_amount'  => ['nullable', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string'],
        ]);
    }
}
