<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Organization;
use App\Models\Setting;
use App\Services\InquiryService;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    /** Publiczna strona firmowa per-tenant: /o/{slug} — z formularzem zapytania. */
    public function show(string $slug)
    {
        $org = Organization::where('slug', $slug)->firstOrFail();
        $cms = Setting::withoutGlobalScopes()
            ->where('organization_id', $org->id)
            ->where('group', 'public_website')
            ->pluck('value', 'key');

        return view('public.page', compact('org', 'cms'));
    }

    /** Form POST z publicznej strony (gdy ktoś otworzy /o/{slug} bez JS-a). */
    public function submitInquiry(Request $request, string $slug)
    {
        $org = Organization::where('slug', $slug)->firstOrFail();

        if ($request->filled('hp_field')) {
            return back()->with('success', 'Dziękujemy! Skontaktujemy się wkrótce.');
        }

        $data = $request->validate([
            'client_name'    => ['required', 'string', 'max:190'],
            'client_email'   => ['required', 'email', 'max:190'],
            'client_phone'   => ['nullable', 'string', 'max:40'],
            'from_address'   => ['required', 'string', 'max:255'],
            'to_address'     => ['required', 'string', 'max:255'],
            'transport_date' => ['nullable', 'date'],
            'horses_count'   => ['nullable', 'integer', 'min:1', 'max:20'],
            'notes'          => ['nullable', 'string', 'max:2000'],
        ]);

        InquiryService::create($org, $data + ['source' => 'public_page'], $request->ip(), $request->userAgent());

        return back()->with('success', 'Dziękujemy za zapytanie! Skontaktujemy się wkrótce.');
    }

    /** JS-snippet do osadzenia na zewnętrznej WWW: <script src="/widget.js?org={slug}"></script>. */
    public function widgetScript(Request $request)
    {
        $slug = $request->string('org')->toString();
        $org = Organization::where('slug', $slug)->firstOrFail();

        $apiUrl = url("/api/o/{$org->slug}/inquiry");
        $brandName = e($org->name);

        $js = <<<JS
(function () {
    var container = document.currentScript.parentNode;
    var box = document.createElement('div');
    box.style.cssText = 'font-family: system-ui, sans-serif; max-width: 480px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff;';
    box.innerHTML =
        '<div style="font-size:18px; font-weight:600; margin-bottom:10px;">Zapytanie o transport — {$brandName}</div>' +
        '<form id="gt-inq-form" style="display:grid; gap:8px;">' +
            '<input name="client_name" required placeholder="Imię i nazwisko *" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;">' +
            '<input name="client_email" required type="email" placeholder="E-mail *" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;">' +
            '<input name="client_phone" placeholder="Telefon" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;">' +
            '<input name="from_address" required placeholder="Skąd (adres / miejscowość) *" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;">' +
            '<input name="to_address" required placeholder="Dokąd *" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;">' +
            '<div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">' +
                '<input name="transport_date" type="date" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;">' +
                '<input name="horses_count" type="number" min="1" value="1" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;">' +
            '</div>' +
            '<textarea name="notes" rows="2" placeholder="Dodatkowe informacje" style="padding:8px; border:1px solid #d1d5db; border-radius:6px;"></textarea>' +
            '<input type="text" name="hp_field" style="display:none" tabindex="-1" autocomplete="off">' +
            '<button type="submit" style="padding:10px; background:#4f46e5; color:#fff; border:0; border-radius:6px; font-weight:600; cursor:pointer;">Wyślij zapytanie</button>' +
            '<div id="gt-inq-msg" style="font-size:13px; color:#16a34a;"></div>' +
        '</form>';
    container.appendChild(box);

    box.querySelector('form').addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(e.target);
        var data = {};
        fd.forEach(function (v, k) { data[k] = v; });
        data.source = 'widget';
        fetch('{$apiUrl}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(data),
        }).then(function (r) { return r.json(); }).then(function (json) {
            var msg = box.querySelector('#gt-inq-msg');
            if (json.ok) {
                e.target.reset();
                msg.style.color = '#16a34a';
                msg.textContent = json.message || 'Dziękujemy!';
            } else {
                msg.style.color = '#dc2626';
                msg.textContent = json.error || 'Błąd. Spróbuj ponownie.';
            }
        }).catch(function () {
            box.querySelector('#gt-inq-msg').textContent = 'Błąd sieci. Spróbuj ponownie.';
        });
    });
})();
JS;

        return response($js, 200, [
            'Content-Type'  => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
