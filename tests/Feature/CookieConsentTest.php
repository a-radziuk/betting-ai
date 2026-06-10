<?php

namespace Tests\Feature;

use App\Models\CookieConsent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_cookie_banner_is_hidden_when_feature_flag_is_disabled(): void
    {
        config(['features.cookie_consent' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('id="cookie-consent-banner"', false)
            ->assertDontSee('Cookie settings', false);
    }

    public function test_cookie_banner_is_rendered_when_feature_flag_is_enabled(): void
    {
        config(['features.cookie_consent' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('id="cookie-consent-banner"', false)
            ->assertSee('We use cookies', false)
            ->assertSee('Cookie settings', false)
            ->assertSee('cookieConsentConfig', false);
    }

    public function test_guest_can_record_rejected_consent(): void
    {
        config(['features.cookie_consent' => true]);

        $consentUuid = (string) Str::uuid();

        $this->postJson(route('cookie-consent.store'), [
            'consent_uuid' => $consentUuid,
            'action' => CookieConsent::ACTION_REJECTED_ALL,
            'categories' => [
                'essential' => true,
                'analytics' => false,
                'marketing' => false,
            ],
        ])
            ->assertOk()
            ->assertJson([
                'consent_uuid' => $consentUuid,
                'action' => CookieConsent::ACTION_REJECTED_ALL,
            ]);

        $this->assertDatabaseHas('cookie_consents', [
            'consent_uuid' => $consentUuid,
            'user_id' => null,
            'action' => CookieConsent::ACTION_REJECTED_ALL,
        ]);
    }

    public function test_authenticated_user_can_record_customized_consent(): void
    {
        config(['features.cookie_consent' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('cookie-consent.store'), [
                'action' => CookieConsent::ACTION_CUSTOMIZED,
                'categories' => [
                    'essential' => true,
                    'analytics' => true,
                    'marketing' => false,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('categories.analytics', true)
            ->assertJsonPath('categories.marketing', false);

        $this->assertDatabaseHas('cookie_consents', [
            'user_id' => $user->id,
            'action' => CookieConsent::ACTION_CUSTOMIZED,
        ]);
    }

    public function test_withdrawal_marks_previous_consent_and_stores_new_record(): void
    {
        config(['features.cookie_consent' => true]);

        $consentUuid = (string) Str::uuid();

        CookieConsent::query()->create([
            'consent_uuid' => $consentUuid,
            'version' => '1',
            'action' => CookieConsent::ACTION_ACCEPTED_ALL,
            'categories' => [
                'essential' => true,
                'analytics' => true,
                'marketing' => true,
            ],
        ]);

        $this->postJson(route('cookie-consent.store'), [
            'consent_uuid' => $consentUuid,
            'action' => CookieConsent::ACTION_WITHDRAWN,
            'categories' => [
                'essential' => true,
                'analytics' => false,
                'marketing' => false,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('cookie_consents', [
            'consent_uuid' => $consentUuid,
            'action' => CookieConsent::ACTION_ACCEPTED_ALL,
        ]);

        $this->assertNotNull(
            CookieConsent::query()
                ->where('consent_uuid', $consentUuid)
                ->where('action', CookieConsent::ACTION_ACCEPTED_ALL)
                ->value('withdrawn_at'),
        );

        $this->assertDatabaseHas('cookie_consents', [
            'consent_uuid' => $consentUuid,
            'action' => CookieConsent::ACTION_WITHDRAWN,
        ]);
    }

    public function test_store_endpoint_returns_not_found_when_feature_flag_is_disabled(): void
    {
        config(['features.cookie_consent' => false]);

        $this->postJson(route('cookie-consent.store'), [
            'action' => CookieConsent::ACTION_REJECTED_ALL,
            'categories' => [
                'essential' => true,
                'analytics' => false,
                'marketing' => false,
            ],
        ])->assertNotFound();
    }
}
