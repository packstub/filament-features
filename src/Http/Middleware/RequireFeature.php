<?php

namespace Packstub\Features\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Packstub\Features\Features;

/**
 * `->middleware('feature:api_access')` — 403 (or redirect to the upgrade
 * URL) unless the feature is active for the current Filament tenant.
 */
class RequireFeature
{
    public function __construct(protected Features $features) {}

    public function handle(Request $request, Closure $next, string $feature, string $mode = 'abort'): mixed
    {
        if ($this->features->active($feature)) {
            return $next($request);
        }

        if ($mode === 'redirect' && ($url = $this->features->upgradeUrl())) {
            return redirect()->to($url);
        }

        abort(403, __('packstub-features::features.errors.inactive', ['feature' => $this->features->feature($feature)->label]));
    }
}
