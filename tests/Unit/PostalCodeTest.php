<?php

namespace Scarneros\JapanPostalCodes\Tests\Unit;

use Scarneros\JapanPostalCodes\JapanPostalCodes;
use Scarneros\JapanPostalCodes\Models\JapanPostalCode;
use Scarneros\JapanPostalCodes\Tests\TestCase;

/**
 * Unit tests for the JapanPostalCodes service and facade.
 */
class PostalCodeTest extends TestCase
{
    /**
     * Seed a test postal code row before each test.
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

    // -----------------------------------------------------------------------
    //  normalize()
    // -----------------------------------------------------------------------

    /** @test */
    public function it_normalizes_full_width_digits(): void
    {
        $this->assertSame('1600023', JapanPostalCodes::normalize('１６０００２３'));
    }

    /** @test */
    public function it_normalizes_full_width_hyphen(): void
    {
        $this->assertSame('1600023', JapanPostalCodes::normalize('１６０ー００２３'));
    }

    /** @test */
    public function it_removes_hyphens_from_half_width_input(): void
    {
        $this->assertSame('1600023', JapanPostalCodes::normalize('160-0023'));
    }

    /** @test */
    public function it_strips_all_non_digit_characters(): void
    {
        $this->assertSame('1600023', JapanPostalCodes::normalize('160 0023'));
    }

    /** @test */
    public function it_handles_already_normalized_input(): void
    {
        $this->assertSame('1600023', JapanPostalCodes::normalize('1600023'));
    }

    // -----------------------------------------------------------------------
    //  format()
    // -----------------------------------------------------------------------

    /** @test */
    public function it_formats_a_seven_digit_string(): void
    {
        $this->assertSame('160-0023', JapanPostalCodes::format('1600023'));
    }

    /** @test */
    public function it_formats_a_postal_code_with_hyphen(): void
    {
        $this->assertSame('160-0023', JapanPostalCodes::format('160-0023'));
    }

    /** @test */
    public function it_returns_original_if_not_seven_digits(): void
    {
        $this->assertSame('123', JapanPostalCodes::format('123'));
    }

    // -----------------------------------------------------------------------
    //  lookup() / search()
    // -----------------------------------------------------------------------

    /** @test */
    public function it_lookups_by_raw_postal_code(): void
    {
        $results = (new JapanPostalCodes)->lookup('1600023');

        $this->assertCount(1, $results);
        $this->assertSame('東京都', $results->first()['prefecture']);
    }

    /** @test */
    public function it_lookups_by_hyphenated_postal_code(): void
    {
        $results = (new JapanPostalCodes)->lookup('160-0023');

        $this->assertCount(1, $results);
        $this->assertSame('新宿区', $results->first()['city']);
    }

    /** @test */
    public function it_lookups_by_full_width_postal_code(): void
    {
        $results = (new JapanPostalCodes)->lookup('１６０ー００２３');

        $this->assertCount(1, $results);
        $this->assertSame('1600023', $results->first()['postal_code']);
    }

    /** @test */
    public function search_is_an_alias_for_lookup(): void
    {
        $lookup = (new JapanPostalCodes)->lookup('1600023');
        $search = (new JapanPostalCodes)->search('1600023');

        $this->assertEquals($lookup, $search);
    }

    /** @test */
    public function it_returns_empty_collection_for_unknown_postal_code(): void
    {
        $results = (new JapanPostalCodes)->lookup('9999999');

        $this->assertTrue($results->isEmpty());
    }

    // -----------------------------------------------------------------------
    //  Facade
    // -----------------------------------------------------------------------

    /** @test */
    public function facade_resolves_correctly(): void
    {
        $results = \PostalCode::lookup('160-0023');

        $this->assertCount(1, $results);
    }

    /** @test */
    public function facade_normalize_works(): void
    {
        $this->assertSame('1600023', \PostalCode::normalize('１６０ー００２３'));
    }

    // -----------------------------------------------------------------------
    //  Model accessors
    // -----------------------------------------------------------------------

    /** @test */
    public function model_address_accessor_returns_full_address(): void
    {
        $model = JapanPostalCode::first();

        $this->assertSame('東京都新宿区西新宿', $model->address);
    }

    /** @test */
    public function model_kana_accessor_returns_spaced_kana(): void
    {
        $model = JapanPostalCode::first();

        $this->assertSame('トウキョウト シンジュクク ニシシンジュク', $model->address_kana);
    }

    /** @test */
    public function model_romaji_accessor_returns_spaced_romaji(): void
    {
        $model = JapanPostalCode::first();

        $this->assertSame('TOKYO SHINJUKU-KU NISHI-SHINJUKU', $model->address_romaji);
    }
}
