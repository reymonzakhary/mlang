<?php

namespace Upon\Mlang\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

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
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
            config(['app.locale' => Session::get('locale')]);
        } else {
            foreach (Config::get('mlang.languages') as $lang) {
                if(
                    in_array(
                        $lang, preg_split('/[,;]/', $request->server('HTTP_ACCEPT_LANGUAGE')), true
                    )
                ) {
                    App::setLocale($lang);
                    config(['app.locale' => $lang]);
                    Session::put('locale', $lang);
                    break;
                }
            }
        }

        return $next($request);
    }
}
