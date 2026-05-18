<?php

declare(strict_types=1);

namespace App\Services\Ksef;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Klucz publiczny Ministerstwa Finansów do szyfrowania tokenu przy InitToken
 * w KSeF. MF publikuje osobny klucz dla sandbox (test) i produkcji (prod).
 *
 * Klucz pobieramy raz, cache w storage/app/ksef/public-keys/ + invalidate
 * po 30 dniach.
 */
final class MfPublicKey
{
    private const URLS = [
        'test'       => 'https://ksef-test.mf.gov.pl/api/public-keys/PublicKey/PublicKey.pem',
        'production' => 'https://ksef.mf.gov.pl/api/public-keys/PublicKey/PublicKey.pem',
    ];

    private const CACHE_DAYS = 30;

    /** Zwraca treść klucza publicznego w formacie PEM. */
    public static function get(string $mode): string
    {
        if (! isset(self::URLS[$mode])) {
            throw new RuntimeException("Nieznany tryb KSeF: {$mode}");
        }

        $path = sprintf('ksef/public-keys/%s.pem', $mode);
        $disk = Storage::disk('local');

        if ($disk->exists($path) && $disk->lastModified($path) > now()->subDays(self::CACHE_DAYS)->timestamp) {
            return $disk->get($path);
        }

        $resp = Http::timeout(20)->get(self::URLS[$mode]);
        if (! $resp->ok()) {
            throw new RuntimeException("MF public key HTTP {$resp->status()}");
        }

        $pem = $resp->body();
        // Sanity check — czy PEM faktycznie wygląda jak klucz.
        if (! str_contains($pem, 'BEGIN PUBLIC KEY')) {
            throw new RuntimeException('MF public key: nieprawidłowy format (oczekiwano PEM).');
        }

        $disk->put($path, $pem);
        return $pem;
    }

    /**
     * Szyfruje token użytkownika kluczem publicznym MF (RSA-OAEP / SHA-256).
     * Wynik to base64-encoded ciphertext - to wstawiamy do <Token> w InitSessionTokenRequest.
     */
    public static function encryptToken(string $mode, string $token): string
    {
        $publicKey = openssl_pkey_get_public(self::get($mode));
        if ($publicKey === false) {
            throw new RuntimeException('Nie udało się sparsować klucza publicznego MF.');
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($token, $encrypted, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $ok) {
            throw new RuntimeException('Szyfrowanie tokenu kluczem MF nie powiodło się.');
        }

        return base64_encode($encrypted);
    }
}
