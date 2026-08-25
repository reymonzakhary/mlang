<?php

namespace Upon\Mlang\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Upon\Mlang\Helpers\LanguageHelper;

/**
 * Detects the request locale and applies it to the application.
 *
 * Sources are tried in the order given by `mlang.detect_locale_from`:
 *   route   – a `{locale}` route parameter (see Route::localized())
 *   segment – the first URL segment (/fr/products)
 *   query   – ?lang=fr (parameter name from `mlang.locale_query_key`)
 *   session – a previously stored choice
 *   header  – the Accept-Language header
 * Only locales listed in `mlang.languages` are accepted; otherwise the
 * fallback language is used. The result is remembered in the session.
 */
class DetectUserLanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detect($request) ?? LanguageHelper::getFallbackLanguage();

        App::setLocale($locale);
        config(['app.locale' => $locale]);

        if ($request->hasSession()) {
            $request->session()->put('locale', $locale);
        }

        return $next($request);
    }

    /**
     * Find the first configured locale supplied by one of the enabled sources.
     *
     * @param Request $request
     * @return string|null
     */
    public function detect(Request $request): ?string
    {
        $supported = LanguageHelper::getConfiguredLanguages();
        $sources = Config::get('mlang.detect_locale_from', ['route', 'segment', 'query', 'session', 'header']);

        foreach ($sources as $source) {
            $candidate = match ($source) {
                'route' => $request->route()?->parameter('locale'),
                'segment' => $request->segment(1),
                'query' => $request->query(Config::get('mlang.locale_query_key', 'lang')),
                'session' => $request->hasSession() ? $request->session()->get('locale') : null,
                'header' => $this->fromHeader($request, $supported),
                default => null,
            };

            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Best supported match from the Accept-Language header, or null.
     *
     * @param Request  $request
     * @param string[] $supported
     * @return string|null
     */
    protected function fromHeader(Request $request, array $supported): ?string
    {
        $header = $request->header('Accept-Language');

        if (!$header) {
            return null;
        }

        $locale = LanguageHelper::parseAcceptLanguageHeader($header);

        // parseAcceptLanguageHeader() returns the fallback when nothing matched;
        // only accept it if the header really mentioned it.
        return str_contains(strtolower($header), strtolower($locale)) ? $locale : null;
    }
}
