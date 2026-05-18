<?php

declare(strict_types=1);

namespace App\Services\Ksef;

use App\Models\Invoice;
use App\Models\Organization;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Klient HTTP dla KSeF (Krajowy System e-Faktur).
 *
 * Tryby:
 *   - disabled    — funkcja KSeF wyłączona, faktura pozostaje w statusie 'manual'
 *   - test        — sandbox MF (ksef-test.mf.gov.pl)
 *   - production  — live (ksef.mf.gov.pl)
 *
 * Flow:
 *   1. AuthorisationChallenge — pobranie wyzwania
 *   2. InitToken              — autoryzacja sesji tokenem (lub podpisem certyfikatu)
 *   3. Invoice/Send           — wysyłka XML
 *   4. Invoice/Status         — odpytanie o status + ewentualnie UPO
 *
 * Pełna implementacja autoryzacji XAdES wymaga prawdziwego certyfikatu KSeF.
 * Tu zapewniamy strukturę i call-out do KSeF API; dla trybu test wystarczy token
 * uzyskany z https://ksef-test.mf.gov.pl/web/login.
 */
final class KsefService
{
    private const URLS = [
        'test'       => 'https://ksef-test.mf.gov.pl/api',
        'production' => 'https://ksef.mf.gov.pl/api',
    ];

    public static function isEnabled(Organization $org): bool
    {
        return in_array($org->ksef_mode, ['test', 'production'], true);
    }

    /**
     * Sprawdza status wcześniej wysłanej faktury i pobiera UPO (Urzędowe
     * Poświadczenie Otrzymania) jako PDF jeśli faktura już została
     * zaakceptowana przez KSeF.
     *
     * Zwraca true gdy UPO zostało pobrane i zapisane (status: confirmed).
     */
    public static function fetchStatus(Invoice $invoice): bool
    {
        $org = $invoice->organization;
        if (! self::isEnabled($org) || ! $invoice->ksef_reference) {
            return false;
        }

        $session = self::openSession($org);

        // Sprawdź status referencji.
        $statusResp = Http::timeout(20)
            ->withHeaders(['SessionToken' => $session, 'Accept' => 'application/json'])
            ->get(self::baseUrl($org) . "/online/Invoice/Status/{$invoice->ksef_reference}");

        if (! $statusResp->ok()) {
            $invoice->update(['ksef_response' => $statusResp->json() ?: ['error' => $statusResp->body()]]);
            return false;
        }

        $status = $statusResp->json();
        $processingCode = (int) ($status['processingCode'] ?? 0);

        // Kod 200 = poprawnie zaakceptowana.
        if ($processingCode !== 200) {
            $invoice->update(['ksef_response' => $status]);
            return false;
        }

        $ksefNumber = $status['invoiceStatus']['ksefReferenceNumber'] ?? $invoice->ksef_reference;

        // Pobierz UPO (PDF).
        $upoResp = Http::timeout(30)
            ->withHeaders(['SessionToken' => $session, 'Accept' => 'application/pdf'])
            ->get(self::baseUrl($org) . "/online/Invoice/Upo/{$ksefNumber}");

        if ($upoResp->ok()) {
            $upoPath = sprintf('ksef/%d/upo/%s.pdf', $org->id, $invoice->number);
            Storage::disk('local')->put($upoPath, $upoResp->body());
            $invoice->update([
                'upo_path'              => $upoPath,
                'ksef_status'           => 'sent',
                'ksef_confirmed_at'     => now(),
                'ksef_response'         => $status,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Wystawia fakturę w KSeF. Aktualizuje invoice.ksef_* na podstawie odpowiedzi.
     * Zwraca true jeśli reference number został przyznany (faktura w KSeF).
     */
    public static function send(Invoice $invoice): bool
    {
        $org = $invoice->organization;
        if (! self::isEnabled($org)) {
            throw new RuntimeException('KSeF jest wyłączony dla tej organizacji.');
        }

        $xml = XmlInvoiceBuilder::build($invoice);
        $xmlPath = sprintf('ksef/%d/invoices/%s.xml', $org->id, $invoice->number);
        Storage::disk('local')->put($xmlPath, $xml);
        $invoice->update(['xml_path' => $xmlPath, 'ksef_status' => 'sending']);

        try {
            $session = self::openSession($org);

            $response = Http::timeout(30)
                ->withHeaders([
                    'SessionToken' => $session,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::baseUrl($org) . '/online/Invoice/Send', [
                    'invoiceHash' => [
                        'hashSHA' => [
                            'algorithm' => 'SHA-256',
                            'encoding'  => 'Base64',
                            'value'     => base64_encode(hash('sha256', $xml, true)),
                        ],
                        'fileSize'  => strlen($xml),
                    ],
                    'invoicePayload' => [
                        'type'         => 'plain',
                        'invoiceBody'  => base64_encode($xml),
                    ],
                ]);

            $body = $response->json();

            if (! $response->ok()) {
                $invoice->update([
                    'ksef_status' => 'rejected',
                    'ksef_response' => $body,
                ]);
                return false;
            }

            $invoice->update([
                'ksef_status'        => 'sent',
                'ksef_reference'     => $body['elementReferenceNumber'] ?? null,
                'ksef_response'      => $body,
                'ksef_session_token' => $session,
                'ksef_sent_at'       => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $invoice->update([
                'ksef_status'   => 'rejected',
                'ksef_response' => ['error' => $e->getMessage()],
            ]);
            throw $e;
        }
    }

    /**
     * Inicjuje sesję KSeF tokenem zapisanym w organizations.ksef_token_encrypted.
     * Zwraca SessionToken używany w kolejnych żądaniach.
     */
    private static function openSession(Organization $org): string
    {
        if (! $org->ksef_token_encrypted) {
            throw new RuntimeException('Brak tokenu KSeF — uzupełnij w panelu ustawień.');
        }

        $token = Crypt::decryptString($org->ksef_token_encrypted);

        // 1. AuthorisationChallenge
        $ch = Http::timeout(20)->post(self::baseUrl($org) . '/online/Session/AuthorisationChallenge', [
            'contextIdentifier' => [
                'type'       => 'onip',
                'identifier' => preg_replace('/[^0-9]/', '', $org->ksef_identifier ?? $org->company_nip ?? ''),
            ],
        ]);
        if (! $ch->ok()) {
            throw new RuntimeException('KSeF challenge: ' . $ch->body());
        }
        $challenge = $ch->json('challenge');
        $timestamp = $ch->json('timestamp');

        // 2. InitToken — token zaszyfrowany RSA publicznym kluczem KSeF.
        // Pełna implementacja wymaga klucza publicznego MF (publikowanego osobno per środowisko).
        // Tutaj wysyłamy token w trybie 'plain' — zadziała tylko w testowym środowisku KSeF.
        $payload = sprintf('<?xml version="1.0" encoding="UTF-8"?>
<ns3:InitSessionTokenRequest xmlns:ns3="http://ksef.mf.gov.pl/schema/gtw/svc/online/auth/request/2021/10/01/0001">
    <ns3:Context>
        <ns3:Challenge>%s</ns3:Challenge>
        <ns3:Identifier xsi:type="ns3:SubjectIdentifierByCompanyType" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <ns3:Identifier>%s</ns3:Identifier>
        </ns3:Identifier>
        <ns3:DocumentType>
            <ns3:Service>KSeF</ns3:Service>
            <ns3:FormCode>
                <ns3:SystemCode>FA (2)</ns3:SystemCode>
                <ns3:SchemaVersion>1-0E</ns3:SchemaVersion>
                <ns3:TargetNamespace>http://crd.gov.pl/wzor/2023/06/29/12648/</ns3:TargetNamespace>
                <ns3:Value>FA</ns3:Value>
            </ns3:FormCode>
        </ns3:DocumentType>
        <ns3:Token>%s</ns3:Token>
    </ns3:Context>
</ns3:InitSessionTokenRequest>',
            htmlspecialchars($challenge, ENT_XML1, 'UTF-8'),
            htmlspecialchars(preg_replace('/[^0-9]/', '', $org->ksef_identifier ?? $org->company_nip ?? ''), ENT_XML1, 'UTF-8'),
            htmlspecialchars($token, ENT_XML1, 'UTF-8'),
        );

        $init = Http::timeout(20)
            ->withBody($payload, 'application/octet-stream')
            ->post(self::baseUrl($org) . '/online/Session/InitToken');

        if (! $init->ok()) {
            throw new RuntimeException('KSeF init session: ' . $init->body());
        }

        $sessionToken = $init->json('sessionToken.token');
        if (! $sessionToken) {
            throw new RuntimeException('KSeF: brak SessionToken w odpowiedzi.');
        }

        return $sessionToken;
    }

    private static function baseUrl(Organization $org): string
    {
        return self::URLS[$org->ksef_mode] ?? self::URLS['test'];
    }
}
