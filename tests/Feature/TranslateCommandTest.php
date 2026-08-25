<?php

namespace Upon\Mlang\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Upon\Mlang\Contracts\TranslatorInterface;
use Upon\Mlang\Events\TranslationCreated;
use Upon\Mlang\Facades\MLang;
use Upon\Mlang\Tests\Fixtures\Article;
use Upon\Mlang\Tests\TestCase;
use Upon\Mlang\Translators\CallbackTranslator;

class TranslateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('mlang:migrate');
        config()->set('mlang.models', [Article::class]);
        Article::create(['name' => 'Chair', 'description' => 'Wooden', 'price' => 5, 'iso' => 'en']);
    }

    public function test_null_translator_copies_source_text(): void
    {
        $this->artisan('mlang:translate')->assertExitCode(0);

        $fr = Article::where('iso', 'fr')->first();
        $this->assertSame('Chair', $fr->name);
        $this->assertEquals(5, $fr->price);
        $this->assertSame(['de', 'en', 'fr'], Article::orderBy('iso')->pluck('iso')->all());
        $this->assertCount(1, Article::pluck('row_id')->unique());
    }

    public function test_custom_driver_translates_only_translatable_attributes(): void
    {
        Event::fake([TranslationCreated::class]);
        $this->app->bind(TranslatorInterface::class, fn () => new CallbackTranslator(
            fn (string $text, string $from, string $to) => "[{$to}] {$text}"
        ));

        $this->artisan('mlang:translate', ['model' => 'Article', '--to' => 'fr'])
            ->expectsOutputToContain('1 rows created')
            ->assertExitCode(0);

        $fr = Article::where('iso', 'fr')->first();
        $this->assertSame('[fr] Chair', $fr->name);
        $this->assertSame('[fr] Wooden', $fr->description);
        $this->assertSame('active', $fr->status);
        $this->assertNull(Article::where('iso', 'de')->first());
        Event::assertDispatched(TranslationCreated::class, fn ($e) => $e->locale === 'fr' && $e->translation->name === '[fr] Chair');
    }

    public function test_is_idempotent_and_available_via_facade(): void
    {
        $this->assertSame(2, MLang::forModel(Article::class)->translateMissing());
        $this->assertSame(0, MLang::forModel(Article::class)->translateMissing());
        $this->assertSame(3, Article::count());
    }

    public function test_unknown_model_fails(): void
    {
        $this->artisan('mlang:translate', ['model' => 'Nope'])->assertExitCode(1);
    }
}
