<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_polish_browser_gets_polish_locale(): void
    {
        $this->get('/', ['Accept-Language' => 'pl-PL,pl;q=0.9,en;q=0.5'])->assertOk();
        $this->assertEquals('pl', app()->getLocale());
    }

    public function test_german_browser_gets_german_locale(): void
    {
        $this->get('/', ['Accept-Language' => 'de-DE,de;q=0.9'])->assertOk();
        $this->assertEquals('de', app()->getLocale());
    }

    public function test_locale_switch_sets_cookie(): void
    {
        $this->get('/locale/en')->assertCookie('locale', 'en');
    }

    public function test_rejects_unknown_locale(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }

    public function test_translation_works_for_known_string(): void
    {
        app()->setLocale('en');
        $this->assertEquals('Quotes', __('Oferty'));

        app()->setLocale('de');
        $this->assertEquals('Rechnungen', __('Faktury'));

        app()->setLocale('pl');
        $this->assertEquals('Oferty', __('Oferty'));  // fallback do klucza
    }

    public function test_user_preferred_locale_is_persisted(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'preferred_locale' => 'pl']);

        $this->actingAs($user)->get('/locale/de');

        $this->assertEquals('de', $user->fresh()->preferred_locale);
    }
}
