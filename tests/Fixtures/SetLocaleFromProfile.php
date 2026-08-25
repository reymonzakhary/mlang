<?php

namespace Upon\Mlang\Tests\Fixtures;

use Closure;
use Illuminate\Support\Facades\App;

/** Simulates an app middleware that sets the locale from the user profile. */
class SetLocaleFromProfile
{
    public function handle($request, Closure $next)
    {
        App::setLocale('de');

        return $next($request);
    }
}
