<?php

namespace Upon\Mlang\Tests\Feature;

use Upon\Mlang\Helpers\LanguageHelper;
use Upon\Mlang\Tests\TestCase;

class LanguageHelperTest extends TestCase
{
    public function test_it_picks_first_configured_language_from_accept_language_header(): void
    {
        $this->assertSame('fr', LanguageHelper::parseAcceptLanguageHeader('fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7'));
        $this->assertSame('de', LanguageHelper::parseAcceptLanguageHeader('es,de;q=0.5'));
    }

    public function test_it_falls_back_when_nothing_matches(): void
    {
        $this->assertSame('en', LanguageHelper::parseAcceptLanguageHeader('es,pt;q=0.8'));
    }
}
