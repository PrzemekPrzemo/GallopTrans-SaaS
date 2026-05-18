# GallopTrans — SaaS dla transportu koni

Multi-tenantowa wersja kalkulatora tras i ofertowania transportu koni
(port z `transportkoni-kalkulator`, który był dedykowany dla jednego klienta).

## Stack

- **Laravel 11** + PHP 8.2+
- **MySQL 8** (multi-tenant: shared DB + `organization_id` w każdej tabeli)
- **Laravel Breeze** — auth (login/register/profil)
- **Laravel Cashier (Stripe)** — billing per-organizacja, 14 dni trialu
- **Tailwind CSS** + Blade + Vite
- **Leaflet.js** + **Openrouteservice** — mapy i routing pojazdów ciężarowych (HGV)
- **NBP API** — kurs EUR/PLN

## Architektura multi-tenancy

- Model `Organization` to root tenanta. Każdy zarejestrowany user przechodzi
  onboarding → utworzenie organizacji → przyznanie roli `owner`.
- Każda tabela biznesowa (`vehicles`, `quotes`, `settings`, …) ma
  `organization_id` z FK + indeksem.
- Modele używają trait `App\Concerns\BelongsToOrganization`, który:
  - rejestruje `OrganizationScope` (globalny filtr po org_id zalogowanego usera)
  - automatycznie wypełnia `organization_id` przy `creating`.
- Middleware `SetTenantContext` (web, append) wstrzykuje `tenant.id` do
  kontenera — dzięki temu nawet poza pętlą `auth()` (np. seedery z imitacją usera)
  scope działa.
- `EnsureOrganization` middleware kieruje user'a bez `organization_id` do `/onboarding`.
- `EnsureSubscribed` blokuje dostęp do kalkulatora/ofert dla orgów po wygasłym trialu
  i bez aktywnej subskrypcji (redirect do `/billing/plans`).

Cashier `Billable` jest na `Organization` (a nie `User`), więc subskrypcja
jest **per-firma** — wielu użytkowników z jednej firmy mieści się w jednym planie.

## Migracja z TransportKoni-Kalkulator

Kod źródłowy z `transportkoni-kalkulator` (PHP vanilla + raw PDO + MySQL) został
**portowany 1:1 logicznie**, ale przepisany do Laravel:

| Oryginał (PHP vanilla)                       | SaaS (Laravel)                                      |
|----------------------------------------------|-----------------------------------------------------|
| `src/Services/CalculatorService.php`         | `app/Services/CalculatorService.php` (identyczna logika, testy w `tests/Unit`) |
| `src/Services/OrsService.php` (cURL)         | `app/Services/OrsService.php` (Laravel `Http`)      |
| `src/Services/NbpService.php`                | `app/Services/NbpService.php` + Eloquent            |
| `src/Services/SettingsService.php`           | `app/Services/SettingsService.php` (tenant-scoped, cache 5 min) |
| `src/Services/QuoteService.php`              | `app/Services/QuoteService.php` + `QuoteNumberGenerator` (per-org sekwencja) |
| `src/Controllers/CalculatorController.php`   | `app/Http/Controllers/CalculatorController.php`     |
| `database/schema.sql`                        | `database/migrations/2026_05_17_19000*.php`         |
| `views/calculator/index.php`                 | `resources/views/calculator/index.blade.php`        |

**Co już działa (MVP):**
- Kalkulator: autocomplete (Pelias), mapa z trasą (Leaflet + ORS GeoJSON),
  live preview wyceny, oszacowanie opłat drogowych dla HGV, zapis jako oferta
  z numerem `OF/RRRR/MM/NNNN` per-organizacja.
- Onboarding: rejestracja → utworzenie organizacji + seed domyślnych settings
  i pojazdu + 14-dniowy trial.
- Stripe: pricing page z 3 planami (Starter/Pro/Business), Checkout,
  portal klienta, webhook (`/stripe/webhook`).
- Dashboard, lista ofert, widok pojedynczej oferty + publiczny link
  `/q/{token}` dla klienta końcowego.

**Co dorzucamy w kolejnych iteracjach (TODO):**
- Generowanie PDF ofert (`barryvdh/laravel-dompdf` już zainstalowany).
- Wysyłka maila do klienta + e-mail z linkiem do publicznej akceptacji.
- Płatności: zaliczki/saldo per oferta, raporty miesięczne, marża.
- Zapytania ofertowe: publiczne API `POST /api/inquiry` + widget HTML+JS
  do osadzenia na WWW klienta.
- Panel kierowcy + iCal feed.
- CMS publicznej WWW (per-tenant).
- Klienci, pojazdy, ustawienia — pełne UI CRUD (na razie tylko seedy +
  edycja parametrów w samym kalkulatorze).

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

Wymagane rozszerzenia PHP: `pdo_mysql`, `bcmath`, `curl`, `mbstring`, `json`.

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

## Testy

```bash
php artisan test
```

`CalculatorServiceTest` weryfikuje że logika wyceny w SaaS daje identyczne
wyniki jak w aplikacji źródłowej (`transportkoni-kalkulator`).

## Branch

Developing on `claude/migrate-calculator-saas-BumqX`.
