<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class EnsureHasUser
{
    public function handle(Request $request, Closure $next)
    {
        // Skip check for installation routes, setup admin routes, and static assets
        if ($request->is('install*') || $request->is('setup-admin*') || $request->is('css/*') || $request->is('js/*')) {
            return $next($request);
        }

        // Check if installed file exists (meaning DB is configured)
        if (file_exists(storage_path('installed'))) {
            try {
                // Check if users table exists and is empty
                if (Schema::hasTable('users') && User::count() === 0) {
                    return redirect()->route('setup-admin.form');
                }
            } catch (\Exception $e) {
                // If DB connection fails, let the app handle it normally or redirect to install
                // For now, we assume if 'installed' file exists, DB should be reachable
            }
        }

        return $next($request);
    }
}
