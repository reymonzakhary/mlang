<?php

namespace Upon\Mlang\Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Upon\Mlang\Facades\MLang;
use Upon\Mlang\Tests\Fixtures\Product;
use Upon\Mlang\Tests\TestCase;

class MlangIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('mlang:migrate');
    }

    public function test_migrate_command_adds_mlang_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('products', ['row_id', 'iso']));
    }

    public function test_observer_sets_row_id_and_iso_on_create(): void
    {
        App::setLocale('fr');
        $product = Product::create(['name' => 'Ordinateur', 'price' => 10]);

        $this->assertSame('fr', $product->iso);
        $this->assertNotNull($product->row_id);
        $this->assertSame($product->getRawOriginal('id'), $product->row_id);
    }

    public function test_create_multi_language_creates_a_row_per_language(): void
    {
        $result = MLang::forModel(Product::class)->createMultiLanguage(
            attributes: ['price' => 99.99],
            languages: ['en', 'fr', 'de'],
            translatedAttributes: [
                'en' => ['name' => 'Laptop'],
                'fr' => ['name' => 'Ordinateur'],
                'de' => ['name' => 'Rechner'],
            ]
        );

        $this->assertCount(3, $result);
        $rows = Product::orderBy('iso')->get();
        $this->assertSame(['de', 'en', 'fr'], $rows->pluck('iso')->all());
        $this->assertCount(1, $rows->pluck('row_id')->unique());
        $this->assertSame('Ordinateur', $rows->firstWhere('iso', 'fr')->name);
    }

    public function test_tr_find_and_tr_where_scope_by_locale(): void
    {
        MLang::forModel(Product::class)->createMultiLanguage(
            attributes: ['price' => 5],
            languages: ['en', 'fr'],
            translatedAttributes: ['en' => ['name' => 'Chair'], 'fr' => ['name' => 'Chaise']]
        );
        $rowId = Product::first()->row_id;

        $this->assertSame('Chaise', Product::trFind($rowId, 'fr')->name);

        App::setLocale('fr');
        $this->assertSame('Chaise', Product::trFind($rowId)->name);
        $this->assertSame(['Chaise'], Product::trWhere('status', 'active')->pluck('name')->all());
    }

    public function test_get_all_translations_and_stats(): void
    {
        MLang::forModel(Product::class)->createMultiLanguage(
            attributes: ['price' => 5],
            languages: ['en', 'fr'],
            translatedAttributes: ['en' => ['name' => 'Chair'], 'fr' => ['name' => 'Chaise']]
        );
        $rowId = Product::first()->row_id;

        $this->assertCount(2, MLang::forModel(Product::class)->getAllTranslations($rowId));

        $stats = MLang::forModel(Product::class)->getStats();
        $this->assertSame(2, $stats['total_records'] ?? $stats['total'] ?? null);
    }
}
