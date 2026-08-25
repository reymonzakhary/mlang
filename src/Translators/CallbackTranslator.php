<?php

namespace Upon\Mlang\Translators;

use Closure;
use Upon\Mlang\Contracts\TranslatorInterface;

/**
 * Wraps a closure `fn (string $text, string $from, string $to): string`.
 * Handy for tests and quick integrations.
 */
class CallbackTranslator implements TranslatorInterface
{
    /**
     * @param Closure $callback
     */
    public function __construct(protected Closure $callback) {}

    /**
     * @inheritDoc
     */
    public function translate(string $text, string $from, string $to): string
    {
        return ($this->callback)($text, $from, $to);
    }
}
