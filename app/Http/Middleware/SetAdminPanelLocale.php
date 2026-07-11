<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class SetAdminPanelLocale
{
    /**
     * The admin panel is Bosnian while the API stays on the default locale,
     * so this must only ever run inside the Filament middleware stack.
     */
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale('bs');
        Carbon::setLocale('bs');

        return $next($request);
    }
}
