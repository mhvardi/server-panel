<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ServerStatsService;

class DashboardController extends Controller
{
    private ServerStatsService $serverStats;

    public function __construct(ServerStatsService $serverStats)
    {
        $this->serverStats = $serverStats;
    }
    public function clearCache()
    {
        abort_unless(auth()->check(), 403);

        $cmd = 'sudo /usr/local/bin/panel-clear-all';
        exec($cmd, $out, $code);

        if ($code !== 0) {
            return back()->with('error', 'پاک‌سازی کش با خطا مواجه شد');
        }

        return back()->with('status', 'تمامی کش‌ها پاک شدند');
    }

    /**
     * Display the dashboard. If ?json=1 is present on the query string or the request
     * expects a JSON response (e.g. from AJAX), return metrics as JSON instead of
     * rendering the blade view.
     */
    public function index(Request $request)
    {
        // If the client explicitly requests JSON (e.g. for live updates)
        if ($request->query('json') === '1' || $request->wantsJson()) {
            $stats = $this->serverStats->getOverview('/');
            return response()->json($stats);
        }

        // Render the full dashboard page
        $stats = $this->serverStats->getOverview('/');
        return view('dashboard.index', compact('stats'));
    }
}
