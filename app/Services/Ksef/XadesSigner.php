<?php

declare(strict_types=1);

namespace App\Services\Ksef;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Podpisuje XML w standardzie XAdES-BES używanym przez KSeF do
 * InitSessionSigned (alternatywa dla InitSessionTokenRequest).
 *
 * Wymaga: certyfikatu firmy (.pem lub .pfx) + prywatny klucz.
 * Klucz prywatny służy do podpisu, certyfikat (publiczny) leci w X509Certificate
 * żeby strona przeciwna mogła zweryfikować podpis.
 *
 * Implementacja zgodna z PKCS#7 / XML DSig (W3C):
 *   - kanonizacja XML (C14N)
 *   - DigestValue (SHA-256 base64)
 *   - SignatureValue (RSA-SHA256 base64)
 *   - X509Certificate w KeyInfo
 */
final class XadesSigner
{
    private const NS_XADES = 'http://uri.etsi.org/01903/v1.3.2#';
    private const NS_DSIG  = 'http://www.w3.org/2000/09/xmldsig#';

    /**
     * @param string $xml    treść XML do podpisania (np. AuthorisationChallenge → InitSessionSignedRequest)
     * @param string $certPath ścieżka do .pem lub .pfx w storage/app/
     * @param string|null $password hasło do PFX (jeśli .pem - null)
     * @return string podpisany XML
     */
    public static function sign(string $xml, string $certPath, ?string $password = null): string
    {
        [$privateKey, $certificate] = self::loadKeyAndCert($certPath, $password);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml, LIBXML_NSCLEAN);

        // 1. Kanonizacja - C14N (Exclusive Canonical XML 1.0).
        $c14n = $dom->C14N(true, false);
        $digestValue = base64_encode(hash('sha256', $c14n, true));

        // 2. SignedInfo - sekcja opisująca co podpisujemy.
        $signedInfoXml = sprintf(
            '<ds:SignedInfo xmlns:ds="%s">' .
                '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>' .
                '<ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>' .
                '<ds:Reference URI="">' .
                    '<ds:Transforms>' .
                        '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>' .
                        '<ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>' .
                    '</ds:Transforms>' .
                    '<ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>' .
                    '<ds:DigestValue>%s</ds:DigestValue>' .
                '</ds:Reference>' .
            '</ds:SignedInfo>',
            self::NS_DSIG,
            $digestValue,
        );

        // 3. Podpisujemy SignedInfo prywatnym kluczem (RSA-SHA256).
        $signature = '';
        $ok = openssl_sign($signedInfoXml, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('XAdES signing: openssl_sign failed.');
        }
        $signatureValue = base64_encode($signature);

        // 4. Składamy pełny element Signature i dopinamy do root XML-a.
        $signatureXml = sprintf(
            '<ds:Signature xmlns:ds="%s">' .
                '%s' .
                '<ds:SignatureValue>%s</ds:SignatureValue>' .
                '<ds:KeyInfo><ds:X509Data><ds:X509Certificate>%s</ds:X509Certificate></ds:X509Data></ds:KeyInfo>' .
            '</ds:Signature>',
            self::NS_DSIG,
            // Usuwamy zewnętrzny xmlns:ds bo już deklarujemy w Signature.
            preg_replace('/\s+xmlns:ds="[^"]*"/', '', $signedInfoXml, 1),
            $signatureValue,
            self::cleanCertForXml($certificate),
        );

        $signatureDom = new DOMDocument();
        $signatureDom->loadXML($signatureXml);
        $importedSig = $dom->importNode($signatureDom->documentElement, true);
        $dom->documentElement->appendChild($importedSig);

        return $dom->saveXML();
    }

    /** @return array{0:\OpenSSLAsymmetricKey,1:string} [privateKey, base64cert without header] */
    private static function loadKeyAndCert(string $certPath, ?string $password): array
    {
        $absPath = Storage::disk('local')->path($certPath);
        if (! is_readable($absPath)) {
            throw new RuntimeException("Cert nieczytelny: {$certPath}");
        }

        $contents = file_get_contents($absPath);

        // PFX/P12 - musi być odszyfrowany hasłem.
        if (str_ends_with(strtolower($certPath), '.pfx') || str_ends_with(strtolower($certPath), '.p12')) {
            $parsed = [];
            if (! openssl_pkcs12_read($contents, $parsed, $password ?? '')) {
                throw new RuntimeException('Nie udało się odczytać PFX: niepoprawne hasło lub uszkodzony plik.');
            }
            $privateKey = openssl_pkey_get_private($parsed['pkey']);
            $certificate = $parsed['cert'];
        } else {
            // PEM zawiera oba w jednym pliku - lub osobne sekcje BEGIN PRIVATE KEY i BEGIN CERTIFICATE.
            $privateKey = openssl_pkey_get_private($contents, $password ?? '');
            preg_match('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $contents, $m);
            $certificate = $m[0] ?? null;
            if (! $certificate) {
                throw new RuntimeException('PEM nie zawiera CERTIFICATE.');
            }
        }

        if (! $privateKey) {
            throw new RuntimeException('Nie udało się załadować klucza prywatnego z certyfikatu.');
        }

        return [$privateKey, $certificate];
    }

    /** Usuwa BEGIN/END markery i nowe linie z PEM cert — XML chce czystego base64. */
    private static function cleanCertForXml(string $certPem): string
    {
        $cert = preg_replace('/-----(BEGIN|END) CERTIFICATE-----/', '', $certPem);
        return preg_replace('/\s+/', '', $cert);
    }
}
