<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class DebugController extends Controller
{
    public function restoreTestData(): RedirectResponse
    {
        if (! app()->isLocal()) {
            abort(404);
        }

        $script = base_path('../test-data/restore-test-data.sh');

        if (! file_exists($script)) {
            Log::error("Restore script not found: {$script}");

            return redirect()->back()->with('toast', [
                'type' => 'error',
                'message' => 'Restore script not found.',
            ]);
        }

        $output = shell_exec("bash {$script} 2>&1");
        Log::info("Test data restore executed: {$output}");

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Test data restored successfully.',
        ]);
    }
}
