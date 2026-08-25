<?php

namespace Upon\Mlang\Tests\Feature;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Upon\Mlang\Events\SharedAttributesSynced;
use Upon\Mlang\Events\TranslationCreated;
use Upon\Mlang\Events\TranslationMissing;
use Upon\Mlang\Facades\MLang;
use Upon\Mlang\Tests\Fixtures\Article;
use Upon\Mlang\Tests\Fixtures\Product;
use Upon\Mlang\Tests\TestCase;

class TranslationRelationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('mlang:migrate');
    }

    /** Create one record in en + fr (no de) and return its row_id. */
    private function seedChair(): int
    {
        MLang::forModel(Product::class)->createMultiLanguage(
            attributes: ['price' => 5],
            languages: ['en', 'fr'],
            translatedAttributes: ['en' => ['name' => 'Chair'], 'fr' => ['name' => 'Chaise']]
        );

        return (int) Product::first()->row_id;
    }

    public function test_translations_relation_returns_all_locale_rows(): void
    {
        $this->seedChair();
        $product = Product::where('iso', 'en')->first();

        $this->assertCount(2, $product->translations);
        $this->assertSame(['Chaise'], $product->otherTranslations()->pluck('name')->all());
        $this->assertSame('Chaise', $product->translation('fr')->name);
        $this->assertSame(['en', 'fr'], collect($product->availableLocales())->sort()->values()->all());
        $this->assertSame(['de'], $product->missingLocales());
        $this->assertTrue($product->hasTranslation('fr'));
        $this->assertFalse($product->hasTranslation('de'));
    }

    public function test_translation_uses_loaded_relation_and_supports_fallback(): void
    {
        Event::fake([TranslationMissing::class]);
        $this->seedChair();
        $product = Product::withTranslations()->where('iso', 'fr')->first();

        $this->assertNull($product->translation('de'));
        Event::assertDispatched(TranslationMissing::class, fn ($e) => $e->locale === 'de' && $e->model === Product::class);

        $this->assertSame('Chair', $product->translation('de', fallback: true)->name);
    }

    public function test_with_fallback_scope_serves_fallback_rows_only_where_locale_is_missing(): void
    {
        $this->seedChair();
        // Second record: en only
        Product::create(['name' => 'Table', 'price' => 9, 'iso' => 'en']);

        $names = Product::withFallback('fr')->orderBy('name')->pluck('name')->all();

        $this->assertSame(['Chaise', 'Table'], $names);
        $this->assertSame(['Chair', 'Table'], Product::withFallback('en')->orderBy('name')->pluck('name')->all());
        $this->assertSame(['Chaise'], Product::inLocale('fr')->pluck('name')->all());
    }

    public function test_tr_where_and_tr_find_honor_fallback_on_query_config(): void
    {
        $rowId = $this->seedChair();
        App::setLocale('de');

        $this->assertSame([], Product::trWhere('status', 'active')->pluck('name')->all());
        $this->assertNull(Product::trFind($rowId));

        config()->set('mlang.fallback_on_query', true);
        Event::fake([TranslationMissing::class]);

        $this->assertSame(['Chair'], Product::trWhere('status', 'active')->pluck('name')->all());
        $this->assertSame('Chair', Product::trFind($rowId)->name);
        Event::assertDispatched(TranslationMissing::class, fn ($e) => $e->rowId === $rowId && $e->locale === 'de');
    }

    public function test_shared_attributes_are_synced_to_sibling_rows_on_update(): void
    {
        Event::fake([SharedAttributesSynced::class]);
        MLang::forModel(Article::class)->createMultiLanguage(
            attributes: ['price' => 5, 'status' => 'draft'],
            languages: ['en', 'fr'],
            translatedAttributes: ['en' => ['name' => 'Chair'], 'fr' => ['name' => 'Chaise']]
        );

        $en = Article::where('iso', 'en')->first();
        $en->update(['price' => 42, 'status' => 'active', 'name' => 'Armchair']);

        $fr = Article::where('iso', 'fr')->first();
        $this->assertEquals(42, $fr->price);
        $this->assertSame('active', $fr->status);
        $this->assertSame('Chaise', $fr->name, 'translatable attributes must not be synced');

        Event::assertDispatched(SharedAttributesSynced::class, fn ($e) => $e->affected === 1
            && array_keys($e->attributes) === ['price', 'status']);
    }

    public function test_models_without_translatable_declaration_do_not_sync(): void
    {
        $this->seedChair();
        Product::where('iso', 'en')->first()->update(['price' => 42]);

        $this->assertEquals(5, Product::where('iso', 'fr')->first()->price);
    }

    public function test_translation_created_event_fires_for_bulk_and_single_creates(): void
    {
        Event::fake([TranslationCreated::class]);
        $this->seedChair();
        Event::assertDispatchedTimes(TranslationCreated::class, 2);

        Product::create(['name' => 'Table', 'price' => 9]);
        Event::assertDispatchedTimes(TranslationCreated::class, 3);
        Event::assertDispatched(TranslationCreated::class, fn ($e) => $e->locale === 'en' && $e->translation->name === 'Table');
    }

    public function test_shared_attribute_helpers(): void
    {
        $article = new Article(['name' => 'x', 'description' => 'y', 'price' => 1, 'status' => 'active']);

        $this->assertSame(['name', 'description'], $article->getTranslatableAttributes());
        $this->assertSame(['price', 'status'], $article->getSharedAttributes());
        $this->assertSame([], (new Product)->getTranslatableAttributes());
    }
}
