<?php

namespace App\Http\Controllers;

use App\Services\CronJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CronJobController extends Controller
{
    public function __construct(private readonly CronJobService $cron)
    {
    }

    public function index()
    {
        $jobs = $this->cron->listJobs();
        $config = $this->cron->getConfig();
        return view('cronjobs.index', compact('jobs', 'config'));
    }

    public function create()
    {
        $config = $this->cron->getConfig();
        return view('cronjobs.create', compact('config'));
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'schedule' => 'required|string|max:64',
            'command' => 'required|string|max:2000',
            'run_as' => 'nullable|string|max:32',
            'enabled' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        try {
            $id = $this->cron->create(
                $request->string('name')->toString(),
                $request->string('schedule')->toString(),
                $request->string('command')->toString(),
                $request->input('run_as'),
                (bool) $request->input('enabled', true)
            );

            return redirect()->route('cronjobs.index')->with('success', 'وظیفه زمانبندی شده با موفقیت ایجاد شد.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function edit(string $id)
    {
        $job = $this->cron->getJob($id);
        if (!$job) {
            return redirect()->route('cronjobs.index')->with('error', 'وظیفه زمانبندی شده یافت نشد.');
        }
        $config = $this->cron->getConfig();
        return view('cronjobs.edit', compact('job', 'config'));
    }

    public function update(Request $request, string $id)
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:120',
            'schedule' => 'required|string|max:64',
            'command' => 'required|string|max:2000',
            'run_as' => 'nullable|string|max:32',
            'enabled' => 'nullable|boolean',
        ]);

        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        try {
            $this->cron->update(
                $id,
                $request->string('name')->toString(),
                $request->string('schedule')->toString(),
                $request->string('command')->toString(),
                $request->input('run_as'),
                (bool) $request->input('enabled', true)
            );
            return redirect()->route('cronjobs.index')->with('success', 'وظیفه زمانبندی شده با موفقیت بروزرسانی شد.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->cron->delete($id);
            return redirect()->route('cronjobs.index')->with('success', 'وظیفه زمانبندی شده با موفقیت حذف شد.');
        } catch (\Throwable $e) {
            return redirect()->route('cronjobs.index')->with('error', $e->getMessage());
        }
    }

    public function toggle(string $id)
    {
        try {
            $this->cron->toggle($id);
            return redirect()->route('cronjobs.index')->with('success', 'وضعیت وظیفه زمانبندی شده بروزرسانی شد.');
        } catch (\Throwable $e) {
            return redirect()->route('cronjobs.index')->with('error', $e->getMessage());
        }
    }
}
