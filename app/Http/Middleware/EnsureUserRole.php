<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please sign in to continue.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        if (! empty($roles) && ! in_array($user->normalizedRole(), $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'That action is not available for this account.'], 403);
            }

            $target = $user->dashboardPath();
            $targetPath = rtrim((string) (parse_url($target, PHP_URL_PATH) ?: ''), '/') ?: '/';
            $currentPath = rtrim($request->getPathInfo(), '/') ?: '/';

            if ($currentPath === $targetPath) {
                return redirect()
                    ->route('home')
                    ->with('error', 'That page is for a different account type.');
            }

            return redirect()
                ->to($target)
                ->with('error', 'That page is for a different account type. You were sent back to your dashboard.');
        }

        return $next($request);
    }
}
