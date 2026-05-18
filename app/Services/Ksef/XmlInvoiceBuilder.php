<?php

declare(strict_types=1);

namespace App\Services\Ksef;

use App\Models\Invoice;
use DOMDocument;

/**
 * Generuje XML faktury w schemie FA(2) używanej przez KSeF.
 *
 * UWAGA: To jest minimalna, ale zgodna z XSD struktura. Dla prawdziwych faktur
 * skomplikowanych (korekty, zaliczki, multi-VAT, eksport, MPP) trzeba dorzucić
 * dodatkowe sekcje wg dokumentacji: https://ksef.podatki.gov.pl/.
 */
final class XmlInvoiceBuilder
{
    public static function build(Invoice $invoice): string
    {
        $org = $invoice->organization;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $root = $dom->createElementNS('http://crd.gov.pl/wzor/2023/06/29/12648/', 'Faktura');
        $root->setAttribute('xmlns:etd', 'http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2022/01/05/eD/DefinicjeTypy/');
        $dom->appendChild($root);

        // === Naglowek ===
        $naglowek = $dom->createElement('Naglowek');
        $naglowek->appendChild($dom->createElement('KodFormularza', 'FA'))->setAttribute('kodSystemowy', 'FA (2)');
        $naglowek->appendChild($dom->createElement('WariantFormularza', '2'));
        $naglowek->appendChild($dom->createElement('DataWytworzeniaFa', $invoice->issued_at->format('Y-m-d\TH:i:s\Z')));
        $naglowek->appendChild($dom->createElement('SystemInfo', 'GallopTrans SaaS'));
        $root->appendChild($naglowek);

        // === Podmiot1 (sprzedawca) ===
        $podmiot1 = $dom->createElement('Podmiot1');
        $daneSprzedawcy = $dom->createElement('DaneIdentyfikacyjne');
        $daneSprzedawcy->appendChild($dom->createElement('NIP', self::nip($org->ksef_identifier ?? $org->company_nip)));
        $daneSprzedawcy->appendChild($dom->createElement('Nazwa', $org->name));
        $podmiot1->appendChild($daneSprzedawcy);

        if ($org->company_address) {
            $adresSp = $dom->createElement('Adres');
            $adresSp->appendChild($dom->createElement('KodKraju', 'PL'));
            $adresSp->appendChild($dom->createElement('AdresL1', $org->company_address));
            $podmiot1->appendChild($adresSp);
        }
        $root->appendChild($podmiot1);

        // === Podmiot2 (nabywca) ===
        $podmiot2 = $dom->createElement('Podmiot2');
        $daneNab = $dom->createElement('DaneIdentyfikacyjne');
        if ($invoice->client_nip) {
            $daneNab->appendChild($dom->createElement('NIP', self::nip($invoice->client_nip)));
        } else {
            $daneNab->appendChild($dom->createElement('BrakID'));
        }
        $daneNab->appendChild($dom->createElement('Nazwa', $invoice->client_company ?: $invoice->client_name));
        $podmiot2->appendChild($daneNab);

        if ($invoice->client_address) {
            $adresNab = $dom->createElement('Adres');
            $adresNab->appendChild($dom->createElement('KodKraju', 'PL'));
            $adresNab->appendChild($dom->createElement('AdresL1', $invoice->client_address));
            $podmiot2->appendChild($adresNab);
        }
        $root->appendChild($podmiot2);

        // === Fa (sama faktura) ===
        $fa = $dom->createElement('Fa');
        $fa->appendChild($dom->createElement('KodWaluty', $invoice->currency));
        $fa->appendChild($dom->createElement('P_1', $invoice->issued_at->format('Y-m-d'))); // data wystawienia
        $fa->appendChild($dom->createElement('P_2', $invoice->number));                      // numer faktury
        $fa->appendChild($dom->createElement('P_6', $invoice->sold_at->format('Y-m-d')));    // data sprzedaży

        $fa->appendChild($dom->createElement('P_13_1', self::amount($invoice->subtotal_net))); // suma netto stawka podstawowa
        $fa->appendChild($dom->createElement('P_14_1', self::amount($invoice->vat_amount)));   // suma VAT stawka podstawowa
        $fa->appendChild($dom->createElement('P_15',   self::amount($invoice->total_gross)));  // razem brutto

        $fa->appendChild($dom->createElement('Adnotacje', 'GallopTrans — transport koni'));
        $fa->appendChild($dom->createElement('RodzajFaktury', 'VAT'));

        // === Pozycje faktury ===
        $sort = 1;
        foreach ($invoice->items as $item) {
            $row = $dom->createElement('FaWiersz');
            $row->setAttribute('typ', 'G');
            $row->appendChild($dom->createElement('NrWierszaFa', (string) $sort++));
            $row->appendChild($dom->createElement('P_7', $item->description));
            $row->appendChild($dom->createElement('P_8A', $item->unit ?: 'szt'));
            $row->appendChild($dom->createElement('P_8B', self::amount($item->qty)));
            $row->appendChild($dom->createElement('P_9A', self::amount($item->unit_price_net)));
            $row->appendChild($dom->createElement('P_11', self::amount($item->total_net)));
            $row->appendChild($dom->createElement('P_12', sprintf('%d', (int) $item->vat_percent)));
            $fa->appendChild($row);
        }

        $root->appendChild($fa);

        $xml = $dom->saveXML();
        return $xml ?: '';
    }

    private static function nip(?string $raw): string
    {
        return preg_replace('/[^0-9]/', '', $raw ?? '') ?: '';
    }

    private static function amount(float $v): string
    {
        return number_format($v, 2, '.', '');
    }
}
