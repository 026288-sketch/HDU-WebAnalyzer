<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Patterns to exclude from intended URL redirect
     */
    private const EXCLUDED_PATTERNS = [
        '/services/',
        '/api/',
        'chart-data',
    ];

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Get the intended URL from session
        $intended = $request->session()->get('url.intended');

        // If intended URL matches excluded patterns (AJAX/API endpoints), ignore it
        if ($intended && $this->shouldExcludeIntendedUrl($intended)) {
            $request->session()->forget('url.intended');

            return redirect()->route('dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Check if the intended URL should be excluded from redirect
     */
    private function shouldExcludeIntendedUrl(string $url): bool
    {
        return collect(self::EXCLUDED_PATTERNS)
            ->contains(fn ($pattern) => Str::contains($url, $pattern));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
