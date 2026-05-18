<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inquiry;
use App\Models\Organization;
use Illuminate\Support\Str;

final class InquiryService
{
    /** @param array<string,mixed> $data */
    public static function create(Organization $org, array $data, ?string $ip = null, ?string $ua = null): Inquiry
    {
        $inquiry = Inquiry::withoutGlobalScopes()->create([
            'organization_id' => $org->id,
            'token'           => Str::random(32),
            'client_name'     => $data['client_name'],
            'client_email'    => $data['client_email'],
            'client_phone'    => $data['client_phone'] ?? null,
            'from_address'    => $data['from_address'],
            'to_address'      => $data['to_address'],
            'transport_date'  => $data['transport_date'] ?? null,
            'horses_count'    => (int) ($data['horses_count'] ?? 1),
            'notes'           => $data['notes'] ?? null,
            'source'          => $data['source'] ?? 'widget',
            'status'          => 'new',
            'ip'              => $ip,
            'user_agent'      => $ua ? substr($ua, 0, 255) : null,
        ]);

        NotificationService::notifyOrg(
            $org->id, ['owner', 'admin', 'operator'],
            'inquiry.new',
            'Nowe zapytanie ofertowe',
            sprintf('%s · %s → %s', $data['client_name'], $data['from_address'], $data['to_address']),
            route('inquiries.index'),
        );

        return $inquiry;
    }
}
