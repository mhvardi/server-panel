<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesZipUpdates;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use ZipArchive;

class SettingsController extends Controller
{
    use HandlesZipUpdates;

    public function index()
    {
        return view('settings.index');
    }

    public function updatePanel(Request $request)
    {
        $request->validate(['repo_url' => 'required|url']);
        $repoUrl = $request->input('repo_url');
        $basePath = base_path();
        $output = '';

        $setRemoteProcess = new Process(['git', 'remote', 'set-url', 'origin', $repoUrl]);
        $setRemoteProcess->setWorkingDirectory($basePath);
        $setRemoteProcess->run();
        if (!$setRemoteProcess->isSuccessful()) {
            return back()->with('error', 'Failed to set remote URL: ' . $setRemoteProcess->getErrorOutput());
        }
        $output .= $setRemoteProcess->getOutput() . "\n";

        $pullProcess = new Process(['git', 'pull', 'origin', 'main']);
        $pullProcess->setWorkingDirectory($basePath);
        $pullProcess->run();
        if (!$pullProcess->isSuccessful()) {
            return back()->with('error', 'خطا در دریافت آخرین تغییرات: ' . $pullProcess->getErrorOutput());
        }
        $pullOutput = $pullProcess->getOutput();
        $output .= $pullOutput . "\n";

        return $this->runPostUpdateActions($output, $pullOutput);
    }

    public function manualUpdatePanel(Request $request)
    {
        // Increase time and memory limits for large file processing
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $request->validate(['update_zip' => 'required|file']);

        $zipFile = $request->file('update_zip');
        if ($zipFile->getClientOriginalExtension() !== 'zip') {
            return back()->with('error', 'لطفاً یک فایل با پسوند .zip آپلود کنید.');
        }

        $zip = new ZipArchive;
        $tempDir = storage_path('app/temp_update_' . time());

        if ($zip->open($zipFile->getRealPath()) === TRUE) {
            File::makeDirectory($tempDir, 0755, true, true);
            $zip->extractTo($tempDir);
            $zip->close();

            $extractedFolders = File::directories($tempDir);
            $sourceDir = count($extractedFolders) === 1 && count(File::files($tempDir)) === 0
                ? $extractedFolders[0]
                : $tempDir;

            $this->syncFiles($sourceDir, base_path());

            File::deleteDirectory($tempDir);

            return $this->runPostUpdateActions("Manual update from ZIP completed.", 'composer.json');
        } else {
            return back()->with('error', 'خطا در باز کردن فایل ZIP. لطفاً مطمئن شوید فایل سالم است.');
        }
    }

    private function runPostUpdateActions(string $initialOutput, string $changeIndicator)
    {
        $basePath = base_path();
        $output = $initialOutput;

        if (str_contains($changeIndicator, 'composer.json')) {
            $composerProcess = new Process(['composer', 'install', '--no-dev', '--optimize-autoloader']);
            $composerProcess->setWorkingDirectory($basePath);
            $composerProcess->run();
            if (!$composerProcess->isSuccessful()) {
                return back()->with('error', 'نصب Composer با خطا مواجه شد: ' . $composerProcess->getErrorOutput());
            }
            $output .= "\nComposer install completed.\n" . $composerProcess->getOutput();
        }

        $migrateProcess = new Process(['php', 'artisan', 'migrate', '--force']);
        $migrateProcess->setWorkingDirectory($basePath);
        $migrateProcess->run();
        if (!$migrateProcess->isSuccessful()) {
            return back()->with('error', 'خطا در اجرای مایگریشن‌ها: ' . $migrateProcess->getErrorOutput());
        }
        $output .= "\nMigrations completed.\n" . $migrateProcess->getOutput();

        $cacheProcess = new Process(['php', 'artisan', 'optimize:clear']);
        $cacheProcess->setWorkingDirectory($basePath);
        $cacheProcess->run();
        $output .= "\nCaches cleared.\n";

        return back()->with('success', "Panel update process finished.\n\n" . $output);
    }
}
