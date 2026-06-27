<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Builds secret-free diagnostic context for login / session / CSRF logging.
 *
 * Used to trace intermittent "Page Expired" (419) and can't-stay-logged-in
 * issues in production. It never logs cookie values, tokens, or passwords —
 * only their presence, counts, and the effective session/cookie configuration,
 * which is what's needed to tell apart the common root causes:
 *   - session cookie not sent back at all   -> domain / secure / same-site issue
 *   - duplicate session cookies in one request -> stale cookie scoped to both
 *     ".technest-agency.com" and the subdomain (clearing cookies "fixes" it)
 *   - cookie present but session row missing -> DB session expiry / sweeping
 */
class AuthDiagnostics
{
    /**
     * @return array<string, mixed>
     */
    public static function context(Request $request): array
    {
        $sessionCookieName = (string) config('session.cookie');
        $cookieHeader = (string) $request->headers->get('Cookie', '');

        // Raw-header counts catch duplicate cookies that PHP's $_COOKIE collapses.
        $sessionCookieCount = $sessionCookieName !== ''
            ? substr_count($cookieHeader, $sessionCookieName . '=')
            : 0;
        $xsrfCookieCount = substr_count($cookieHeader, 'XSRF-TOKEN=');

        $session = $request->hasSession() ? $request->session() : null;

        return [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->headers->get('referer'),

            // Effective request scheme as seen *after* TrustProxies.
            'scheme' => $request->getScheme(),
            'is_secure' => $request->isSecure(),
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),

            // Effective session/cookie configuration in play for this request.
            'session_driver' => config('session.driver'),
            'session_cookie' => $sessionCookieName,
            'session_domain' => config('session.domain'),
            'session_secure' => config('session.secure'),
            'session_same_site' => config('session.same_site'),
            'session_lifetime' => config('session.lifetime'),

            // Did the browser actually send the session cookie back, and how many?
            'has_session_cookie' => $sessionCookieCount > 0,
            'session_cookie_count' => $sessionCookieCount, // >1 == duplicate/stale cookies
            'xsrf_cookie_count' => $xsrfCookieCount,
            'total_cookies_sent' => count($request->cookies->all()),

            // Token presence (never the value).
            'has_form_token' => filled($request->input('_token')),
            'has_xsrf_header' => filled($request->header('X-XSRF-TOKEN')),

            // Current session id (safe identifier; not the session payload).
            'session_id' => $session?->getId(),
        ];
    }
}
