<?php

namespace Upon\Mlang\Contracts;

/**
 * A machine-translation driver used by mlang:translate and the facade.
 *
 * Implement this to plug in DeepL, Google, an LLM, or an in-house service, then
 * point `mlang.translator` at your class (it is resolved through the container).
 */
interface TranslatorInterface
{
    /**
     * Translate a single text.
     *
     * @param string $text
     * @param string $from Source locale (e.g. 'en')
     * @param string $to   Target locale (e.g. 'fr')
     * @return string
     */
    public function translate(string $text, string $from, string $to): string;
}
