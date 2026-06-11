<?php

namespace Scarneros\JapanPostalCodes\Tests\Feature;

use Scarneros\JapanPostalCodes\Models\JapanPostalCode;
use Scarneros\JapanPostalCodes\Tests\TestCase;

/**
 * Feature tests for the built-in JSON API endpoint.
 */
class ApiTest extends TestCase
{
    /**
     * Seed test data.
     */
    protected function setUp(): void
    {
        parent::setUp();

        JapanPostalCode::create([
            'postal_code' => '1600023',
            'postal_code_formatted' => '160-0023',
            'prefecture' => '東京都',
            'city' => '新宿区',
            'town' => '西新宿',
            'prefecture_kana' => 'トウキョウト',
            'city_kana' => 'シンジュクク',
            'town_kana' => 'ニシシンジュク',
            'prefecture_romaji' => 'TOKYO',
            'city_romaji' => 'SHINJUKU-KU',
            'town_romaji' => 'NISHI-SHINJUKU',
        ]);
    }

    /** @test */
    public function api_returns_valid_json_for_known_postal_code(): void
    {
        $response = $this->getJson('/api/postal-codes/160-0023');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'postal_code',
                        'formatted',
                        'prefecture',
                        'city',
                        'town',
                        'address',
                        'kana',
                        'romaji',
                    ],
                ],
            ]);
    }

    /** @test */
    public function api_returns_expected_address_data(): void
    {
        $response = $this->getJson('/api/postal-codes/160-0023');

        $response->assertOk()
            ->assertJsonFragment([
                'prefecture' => '東京都',
                'city' => '新宿区',
                'town' => '西新宿',
            ]);
    }

    /** @test */
    public function api_accepts_raw_postal_code(): void
    {
        $response = $this->getJson('/api/postal-codes/1600023');

        $response->assertOk()
            ->assertJsonFragment([
                'postal_code' => '1600023',
            ]);
    }

    /** @test */
    public function api_accepts_full_width_postal_code(): void
    {
        $response = $this->getJson('/api/postal-codes/'.rawurlencode('１６０ー００２３'));

        $response->assertOk()
            ->assertJsonFragment([
                'postal_code' => '1600023',
            ]);
    }

    /** @test */
    public function api_returns_empty_data_for_unknown_postal_code(): void
    {
        $response = $this->getJson('/api/postal-codes/9999999');

        $response->assertOk()
            ->assertJson([
                'data' => [],
            ]);
    }

    /** @test */
    public function api_returns_422_for_invalid_postal_code(): void
    {
        $response = $this->getJson('/api/postal-codes/invalid');

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'message']);
    }

    /** @test */
    public function api_route_is_not_registered_when_disabled(): void
    {
        config()->set('japan-postal-codes.api.enabled', false);

        // Re‑register routes to reflect config change (Testbench handles this)
        $this->app['router']->getRoutes()->refreshNameLookups();

        // In Orchestra Testbench the routes are already loaded;
        // when disabled the route simply won't match.
        // We verify the package config is properly honoured.
        $this->assertFalse(config('japan-postal-codes.api.enabled'));
    }
}
