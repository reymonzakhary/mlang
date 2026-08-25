<?php

namespace Upon\Mlang\Translators;

use Upon\Mlang\Contracts\TranslatorInterface;

/**
 * Default driver: returns the source text unchanged, so generated rows are
 * copies waiting for a human translator.
 */
class NullTranslator implements TranslatorInterface
{
    /**
     * @inheritDoc
     */
    public function translate(string $text, string $from, string $to): string
    {
        return $text;
    }
}
