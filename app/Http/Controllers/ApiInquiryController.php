<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\InquiryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ApiInquiryController extends Controller
{
    public function store(string $slug, Request $request): JsonResponse
    {
        $org = Organization::where('slug', $slug)->first();
        if (! $org) {
            return response()->json(['ok' => false, 'error' => 'Unknown tenant'], 404);
        }

        // Honeypot — boty wypełnią ukryte pole.
        if ($request->filled('hp_field')) {
            return response()->json(['ok' => true]);  // udawaj sukces, ale ignoruj
        }

        $key = 'inquiry:' . $request->ip() . ':' . $slug;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['ok' => false, 'error' => 'Too many requests'], 429);
        }
        RateLimiter::hit($key, 3600);  // 10 / godzinę

        $data = $request->validate([
            'client_name'    => ['required', 'string', 'max:190'],
            'client_email'   => ['required', 'email', 'max:190'],
            'client_phone'   => ['nullable', 'string', 'max:40'],
            'from_address'   => ['required', 'string', 'max:255'],
            'to_address'     => ['required', 'string', 'max:255'],
            'transport_date' => ['nullable', 'date', 'after_or_equal:today'],
            'horses_count'   => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'source'         => ['nullable', 'string', 'max:50'],
        ]);

        $inq = InquiryService::create($org, $data, $request->ip(), $request->userAgent());

        return response()->json([
            'ok' => true,
            'inquiry_id' => $inq->id,
            'message' => 'Dziękujemy! Skontaktujemy się wkrótce.',
        ]);
    }
}
