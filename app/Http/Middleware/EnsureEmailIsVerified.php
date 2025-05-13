<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If the user is null, or if the user is an instance of MustVerifyEmail (which our App\Models\User is)
        // and has not verified their email, then abort.
        // @phpstan-ignore-next-line instanceof.alwaysTrue
        if (! $user || ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) ) {
            // The ($user instanceof MustVerifyEmail) part is what PHPStan might consider redundant
            // if $user is known to be App\Models\User, but it's safer for general Authenticatable users.
            // To strictly address PHPStan's point IF $user is guaranteed to be App\Models\User here:
            // if (! $user || ! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email address is not verified.'], 409);
        }

        return $next($request);
    }
}
