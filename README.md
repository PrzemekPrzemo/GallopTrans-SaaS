# GallopTrans — SaaS dla transportu koni

Multi-tenantowa wersja kalkulatora tras i ofertowania transportu koni
(port z `transportkoni-kalkulator`, który był dedykowany dla jednego klienta).

## Stack

- **Laravel 11** + PHP 8.2+
- **MySQL 8** (multi-tenant: shared DB + `organization_id` w każdej tabeli)
- **Laravel Breeze** (auth) + **Laravel Cashier (Stripe)** (billing per-organizacja)
- **Tailwind CSS** + Blade + Vite
- **Leaflet.js** + **Openrouteservice** — mapy i routing pojazdów ciężarowych (HGV)
- **DomPDF** — generowanie PDF ofert
- **NBP API** — kurs EUR/PLN

## Co działa

### Multi-tenancy
- `Organization` = root tenanta. User po rejestracji przechodzi `/onboarding`
  → tworzy się Organization → user dostaje rolę `owner` + 14 dni trialu.
- Każda tabela biznesowa ma `organization_id` (FK + index).
- Trait `BelongsToOrganization` + `OrganizationScope` automatycznie filtruje
  SELECT i wypełnia FK na save.
- Middleware: `SetTenantContext`, `EnsureOrganization`, `EnsureSubscribed`.
- Cashier `Billable` na `Organization` — subskrypcja jest **per-firma**
  (jeden plan obsługuje wielu userów z firmy).

### Kalkulator i oferty
- Autocomplete adresów (Pelias), mapa Leaflet z trasą GeoJSON z ORS.
- Restrykcje pojazdu HGV (waga, wysokość, długość, osie) — bezpieczne dla 7.5t+.
- Automatyczne oszacowanie opłat drogowych (e-TOLL dla >3.5t).
- Live preview wyceny, zapis jako oferta z numeracją `OF/RRRR/MM/NNNN`
  per-organizacja (lock-for-update na sekwencji).
- Wszystkie trzy tryby trasy: jednorazowo / w obie strony / tam + powrót bezpośredni.
- PLN i EUR (przez kurs NBP).

### PDF + email
- Generowanie PDF ofert (DomPDF) — pobranie dla pracownika i klienta.
- Wysyłka oferty mailem z PDF w załączniku + Markdown HTML body.
- Auto-status `sent` po wysłaniu maila.

### Płatności i raporty
- Rejestrowanie wpłat (brutto → automatyczne netto i VAT na podstawie VAT % oferty).
- Auto-status `accepted` na ofercie po pełnej zapłacie.
- Saldo per oferta, lista wpłat, ostatnie 12 m-cy w raportach + drill-down do miesiąca.

### Publiczne API i widget
- `POST /api/o/{slug}/inquiry` — zapytania ofertowe z zewnętrznych WWW (CORS, rate-limit 10/h/IP, honeypot).
- `/widget.js?org={slug}` — gotowy do osadzenia JS-snippet generujący formularz zapytania.
- `/o/{slug}` — publiczna strona firmowa per-tenant z treściami CMS.
- Moduł `Zapytania ofertowe`: lista, zmiana statusu, jednym kliknięciem `→ Wyceń`
  (prefill kalkulatora danymi z zapytania).

### Panel kierowcy
- `/my-trips` — lista nadchodzących i historycznych tras (przypisanych jako driver lub created_by).
- iCal feed `/calendar/{token}.ics` — subskrypcja w Google/Apple/Outlook calendar.

### CRUD i ustawienia
- Pojazdy: pełny resource controller + form (waga, wysokość, osie, etc.).
- Ustawienia: dane firmy + wszystkie parametry settings pogrupowane (pricing/routing/quotes/mail/public_website) + klucz ORS per-tenant.
- CMS publicznej strony (hero, subtitle, services, contact) edytowalny z poziomu UI.

### Publiczna strona oferty
- `/q/{token}` — link dla klienta końcowego (bez logowania).
- `/q/{token}/pdf` — bezpośrednie pobranie PDF.

## Testy

```bash
php artisan test
```

**39 testów zielonych** pokrywających:
- `CalculatorServiceTest` (5) — parity logiki wyceny z aplikacją źródłową (one-way, round-trip,
  min amount, EUR, dopłaty za konie).
- `QuoteBalanceTest` (2) — netto/VAT z brutto, auto-status po pełnej zapłacie.
- `MultiTenancyTest` (3) — izolacja danych między orgami, auto-fill `organization_id`,
  redirect bez orgu do onboardingu.
- `PublicInquiryApiTest` (4) — tenant scoping, walidacja, 404 dla nieznanego slug, honeypot.
- Breeze auth (25) — login, register, password reset, profile update, email verification.

CI uruchamia testy automatycznie na każdy PR (`.github/workflows/tests.yml`).

## Uruchomienie lokalne

```bash
cp .env.example .env
php artisan key:generate
# w .env ustaw: DB_*, ORS_API_KEY, STRIPE_KEY, STRIPE_SECRET, STRIPE_PRICE_STARTER/PRO/BUSINESS
composer install
npm install && npm run build
php artisan migrate
php artisan serve
```

Wymagane rozszerzenia PHP: `pdo_mysql`, `bcmath`, `curl`, `mbstring`, `json`, `intl`.

## Stripe — konfiguracja

1. Stwórz 3 produkty (`Starter`, `Pro`, `Business`) z cyklicznymi cenami PLN/miesiąc.
2. Skopiuj ID cen (`price_xxx`) do `.env` jako `STRIPE_PRICE_STARTER/PRO/BUSINESS`.
3. Stwórz webhook w Stripe Dashboard:
   - URL: `https://twoja-domena/stripe/webhook`
   - Eventy: minimum `customer.subscription.*`, `invoice.payment_*`, `customer.updated`.
   - Skopiuj `Signing secret` do `.env` jako `STRIPE_WEBHOOK_SECRET`.

## Openrouteservice — klucz API

1. Załóż darmowe konto na <https://openrouteservice.org/dev/#/signup>.
2. Wygeneruj token Standard (geocode + directions).
3. Klucz wpisz w `.env` jako `ORS_API_KEY` *lub* w panelu ustawień organizacji
   (per-tenant) — wartość z bazy ma pierwszeństwo nad ENV.

## Mapa migracji z TransportKoni-Kalkulator

| Oryginał (PHP vanilla)                       | SaaS (Laravel)                                      |
|----------------------------------------------|-----------------------------------------------------|
| `src/Services/CalculatorService.php`         | `app/Services/CalculatorService.php`                |
| `src/Services/OrsService.php` (cURL)         | `app/Services/OrsService.php` (Laravel `Http`)      |
| `src/Services/NbpService.php`                | `app/Services/NbpService.php` + Eloquent            |
| `src/Services/SettingsService.php`           | `app/Services/SettingsService.php` (tenant-scoped, cache 5 min) |
| `src/Services/QuoteService.php`              | `app/Services/QuoteService.php` + `QuoteNumberGenerator` |
| `src/Services/PdfService.php` (mPDF)         | `app/Services/PdfService.php` (DomPDF)              |
| `src/Services/CalendarService.php`           | `app/Services/CalendarService.php`                  |
| `src/Services/InquiryService.php`            | `app/Services/InquiryService.php`                   |
| `src/Services/PaymentService.php`            | `app/Services/PaymentService.php`                   |
| `src/Controllers/CalculatorController.php`   | `app/Http/Controllers/CalculatorController.php`     |
| `database/schema.sql`                        | `database/migrations/2026_05_17_19*.php`            |
| `views/calculator/index.php`                 | `resources/views/calculator/index.blade.php`        |
| `views/calculator/pdf/*.php`                 | `resources/views/quotes/pdf.blade.php`              |
| `views/public/page.php`                      | `resources/views/public/page.blade.php`             |

## Branch

Developing on `claude/migrate-calculator-saas-BumqX`.
