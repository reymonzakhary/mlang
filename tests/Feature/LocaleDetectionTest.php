<?php

namespace Upon\Mlang\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Upon\Mlang\Middleware\DetectUserLanguageMiddleware;
use Upon\Mlang\Tests\TestCase;

class LocaleDetectionTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware(['web', DetectUserLanguageMiddleware::class])
            ->get('/plain', fn () => app()->getLocale());

        Route::localized(function () {
            Route::get('/products', fn () => app()->getLocale())->name('products');
        });
    }

    public function test_uses_fallback_when_nothing_matches(): void
    {
        $this->get('/plain')->assertSee('en');
    }

    public function test_query_string_wins_over_header(): void
    {
        $this->get('/plain?lang=de', ['Accept-Language' => 'fr'])->assertSee('de');
    }

    public function test_accept_language_header_is_honored(): void
    {
        $this->get('/plain', ['Accept-Language' => 'fr-FR,fr;q=0.9'])->assertSee('fr');
    }

    public function test_unsupported_values_are_ignored(): void
    {
        $this->get('/plain?lang=xx', ['Accept-Language' => 'es'])->assertSee('en');
    }

    public function test_locale_is_remembered_in_session(): void
    {
        $this->withSession(['locale' => 'de'])->get('/plain')->assertSee('de');
    }

    public function test_localized_routes_use_the_url_prefix(): void
    {
        $this->get('/fr/products')->assertOk()->assertSee('fr');
        $this->get('/de/products')->assertOk()->assertSee('de');
        $this->get('/xx/products')->assertNotFound();
    }

    public function test_detection_order_is_configurable(): void
    {
        config()->set('mlang.detect_locale_from', ['header']);

        $this->get('/plain?lang=de', ['Accept-Language' => 'fr'])->assertSee('fr');
    }
}
