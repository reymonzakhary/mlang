<?php

namespace Upon\Mlang\Tests\Feature;

use Upon\Mlang\Middleware\DetectUserLanguageMiddleware;
use Upon\Mlang\Tests\Fixtures\SetLocaleFromProfile;
use Upon\Mlang\Tests\TestCase;

/** 3.0.1 compatibility behaviour of the locale middleware. */
class LocaleCompatTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        // Simulates an app that sets the locale from the user profile before MLang runs
        $router->middleware([SetLocaleFromProfile::class, DetectUserLanguageMiddleware::class])
            ->get('/profile', fn () => app()->getLocale());

        $router->middleware(DetectUserLanguageMiddleware::class)
            ->get('/de/about', fn () => app()->getLocale());
    }

    public function test_keeps_a_locale_the_app_already_set(): void
    {
        // the test client sends Accept-Language: en-us by default; blank it to simulate no header
        $this->get('/profile', ['Accept-Language' => ''])->assertSee('de');
    }

    public function test_header_still_overrides_a_preset_locale(): void
    {
        $this->get('/profile', ['Accept-Language' => 'fr'])->assertSee('fr');
    }

    public function test_url_segment_is_opt_in(): void
    {
        $this->get('/de/about', ['Accept-Language' => ''])->assertSee('en');

        config()->set('mlang.detect_locale_from', ['segment']);
        $this->get('/de/about')->assertSee('de');
    }
}
