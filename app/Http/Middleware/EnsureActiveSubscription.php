<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnsureActiveSubscription
{
    // Dynamically determine required features from the admin path.
    // No static map so features can be fully controlled from DB (plans).

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow logout requests to pass through
        if (str_contains($request->path(), 'logout')) {
            return $next($request);
        }
        
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super admins bypass subscription checks
        if (in_array('super_admin', $user->roles->pluck('name')->toArray())) {
            return $next($request);
        }

        // Users without a shop are blocked
        if (! $user->shop) {
            abort(403, 'No shop associated with your account.');
        }

        // Check for active subscription
        if (! $user->shop->hasActiveSubscription()) {
            // Allow access to subscription management pages to prevent redirect loops
            if (str_contains($request->path(), 'manage-subscription') || 
                str_contains($request->path(), 'subscriptions') || 
                $request->routeIs('filament.admin.pages.manage-subscription')) {
                return $next($request);
            }
            
            // For Filament admin panel requests
            if (str_contains($request->path(), 'admin')) {
                // Allow access to admin dashboard and login
                if ($request->path() === 'admin' || $request->path() === 'admin/login') {
                    return $next($request);
                }
                
                // Redirect to subscription management page
                return redirect()->route('filament.admin.pages.manage-subscription')
                    ->with('warning', 'Your shop subscription is inactive or expired. Please activate your subscription to continue.');
            }
            
            // For non-Filament requests
            return redirect()->route('subscriptions.current')
                ->with('warning', 'Your shop subscription is inactive or expired. Please activate your subscription to continue.');
        }
        
        // Check for feature access based on current path, using DB permissions as candidates
        $path = $request->path();
        $resource = $this->extractResourceFromPath($path);
        if ($resource) {
            // Get plan features for logging (support assoc and list formats)
            $planFeatures = $user->shop->activeSubscription()?->plan->features ?? [];
            if (is_array($planFeatures)) {
                $isAssoc = array_keys($planFeatures) !== range(0, count($planFeatures) - 1);
                $planFeatureKeys = $isAssoc ? array_keys($planFeatures) : array_values($planFeatures);
            } else {
                $planFeatureKeys = [];
            }

            // Build candidates purely from user permissions; do NOT filter by plan keys here
            // so that aliasing inside Shop::hasFeature() can grant access (e.g., categories via products).
            $candidates = $this->featureCandidatesFromPermissions($user, $resource);
            // Fallback to convention-based candidates if none found from permissions
            if (empty($candidates)) {
                $singular = Str::singular($resource);
                $candidates = [
                    'manage_' . $resource,
                    'manage_' . $singular,
                ];
            }

            $allowed = false;
            foreach ($candidates as $featureKey) {
                if ($user->shop->hasFeature($featureKey)) {
                    $allowed = true;
                    break;
                }
            }
            if (! $allowed) {
                $subscription = $user->shop->activeSubscription();
                $plan = $subscription?->plan;
                $userPerms = method_exists($user, 'getAllPermissions')
                    ? $user->getAllPermissions()->pluck('name')->all()
                    : [];
                Log::warning('Subscription feature denial', [
                    'shop_id' => $user->shop->id,
                    'subscription_id' => $subscription?->id,
                    'plan_id' => $plan?->id,
                    'path' => $path,
                    'resource' => $resource,
                    'candidates' => array_values($candidates),
                    'plan_features_keys' => $planFeatureKeys,
                    'user_permissions' => $userPerms,
                    'plan_features_raw' => $planFeatures,
                ]);
                abort(403, "Your subscription plan doesn't include access to this feature. Please upgrade your plan.");
            }
        }

        return $next($request);
    }
    
    /**
     * Extract the admin resource segment from path, e.g. admin/products -> products
     */
    protected function extractResourceFromPath(string $path): ?string
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        if (empty($segments)) {
            return null;
        }
        // If first segment is a panel slug (like 'admin'), take the next segment as resource
        if (isset($segments[0]) && $segments[0] === 'admin') {
            return $segments[1] ?? null;
        }
        // Otherwise treat the first segment as resource
        return $segments[0] ?? null;
    }

    /**
     * Build candidate feature keys from the user's DB permissions for a resource.
     * If permission names and feature keys are the same (your design), this keeps it fully dynamic.
     */
    protected function featureCandidatesFromPermissions($user, string $resource): array
    {
        // If Spatie permissions are present, use them. Otherwise return empty to fall back to conventions.
        $permissionNames = method_exists($user, 'getAllPermissions')
            ? $user->getAllPermissions()->pluck('name')->all()
            : [];

        if (empty($permissionNames)) {
            return [];
        }

        $singular = Str::singular($resource);
        $patterns = [
            'manage_' . $resource,
            'manage_' . $singular,
        ];

        $candidates = [];
        foreach ($permissionNames as $perm) {
            foreach ($patterns as $p) {
                $matchesResource = Str::is($p, $perm) || Str::contains($perm, [$p, $resource, $singular]);
                if ($matchesResource) {
                    $candidates[] = $perm;
                    break;
                }
            }
        }

        return array_values(array_unique($candidates));
    }
}
