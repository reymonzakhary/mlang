<?php

namespace Upon\Mlang\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Upon\Mlang\Helpers\SecurityHelper;
use Upon\Mlang\Helpers\LanguageHelper;
use InvalidArgumentException;

class HelpersTest extends TestCase
{
    /** @test */
    public function it_validates_valid_locale()
    {
        $this->assertTrue(SecurityHelper::validateLocale('en'));
        $this->assertTrue(SecurityHelper::validateLocale('fr'));
        $this->assertTrue(SecurityHelper::validateLocale('en-US'));
        $this->assertTrue(SecurityHelper::validateLocale('pt-BR'));
    }

    /** @test */
    public function it_rejects_invalid_locale()
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityHelper::validateLocale('invalid_locale');
    }

    /** @test */
    public function it_validates_valid_table_name()
    {
        $this->assertTrue(SecurityHelper::validateTableName('users'));
        $this->assertTrue(SecurityHelper::validateTableName('user_profiles'));
        $this->assertTrue(SecurityHelper::validateTableName('database.users'));
    }

    /** @test */
    public function it_rejects_invalid_table_name()
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityHelper::validateTableName('users; DROP TABLE users--');
    }

    /** @test */
    public function it_validates_column_name()
    {
        $this->assertTrue(SecurityHelper::validateColumnName('id'));
        $this->assertTrue(SecurityHelper::validateColumnName('row_id'));
        $this->assertTrue(SecurityHelper::validateColumnName('_internal'));
    }

    /** @test */
    public function it_rejects_invalid_column_name()
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityHelper::validateColumnName('invalid-column');
    }

    /** @test */
    public function it_checks_valid_row_id()
    {
        $this->assertTrue(SecurityHelper::isValidRowId(1));
        $this->assertTrue(SecurityHelper::isValidRowId(999));
        $this->assertFalse(SecurityHelper::isValidRowId(0));
        $this->assertFalse(SecurityHelper::isValidRowId(-1));
        $this->assertFalse(SecurityHelper::isValidRowId('abc'));
        $this->assertFalse(SecurityHelper::isValidRowId(1.5));
    }

    /** @test */
    public function it_sanitizes_values()
    {
        $this->assertEquals('test', SecurityHelper::sanitizeValue('  test  '));
        $this->assertEquals('test', SecurityHelper::sanitizeValue("test\x00"));
        $this->assertEquals(123, SecurityHelper::sanitizeValue(123));
    }

    /** @test */
    public function it_gets_language_name()
    {
        $this->assertEquals('English', LanguageHelper::getLanguageName('en'));
        $this->assertEquals('French', LanguageHelper::getLanguageName('fr'));
        $this->assertEquals('German', LanguageHelper::getLanguageName('de'));
    }

    /** @test */
    public function it_parses_accept_language_header()
    {
        // This test assumes 'en' is in the configured languages
        $locale = LanguageHelper::parseAcceptLanguageHeader('fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7');
        $this->assertIsString($locale);
        $this->assertMatchesRegularExpression('/^[a-z]{2}$/', $locale);
    }

    /** @test */
    public function it_validates_locales_array()
    {
        $this->assertTrue(SecurityHelper::validateLocales(['en', 'fr', 'de']));
        $this->assertTrue(SecurityHelper::validateLocales(['en-US', 'fr-FR']));
    }

    /** @test */
    public function it_rejects_invalid_locales_array()
    {
        $this->expectException(InvalidArgumentException::class);
        SecurityHelper::validateLocales(['en', 'invalid_locale', 'fr']);
    }

    /** @test */
    public function it_sanitizes_attributes_array()
    {
        $input = [
            'name' => '  Product  ',
            'description' => "Test\x00Description",
            'price' => 99.99
        ];

        $sanitized = SecurityHelper::sanitizeAttributes($input);

        $this->assertEquals('Product', $sanitized['name']);
        $this->assertEquals('TestDescription', $sanitized['description']);
        $this->assertEquals(99.99, $sanitized['price']);
    }
}
