<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManualExecutionsRequest;
use App\LibraryJobId;
use App\Models\Library;
use App\Services\ManualExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ManualExecutionsController extends Controller
{
    public function files(Request $request, Library $library, ManualExecutionService $service): JsonResponse
    {
        $validated = $request->validate(['path' => ['sometimes', 'string']]);
        $root = realpath($library->base_path);
        $path = realpath($validated['path'] ?? $library->base_path);
        if ($root === false || $path === false || ($path !== $root && ! str_starts_with($path, rtrim($root, '/').'/')) || ! is_dir($path) || ! is_readable($path)) {
            throw ValidationException::withMessages(['path' => 'Choose a readable folder inside this library.']);
        }
        $entries = [];
        foreach (new \DirectoryIterator($path) as $entry) {
            if ($entry->isDot() || $entry->isLink() || str_starts_with($entry->getFilename(), '.')) {
                continue;
            }
            if (! $entry->isDir()) {
                try {
                    $service->resolveFile($library, $entry->getPathname());
                } catch (ValidationException) {
                    continue;
                }
            }
            $entries[] = ['name' => $entry->getFilename(), 'path' => $entry->getPathname(), 'directory' => $entry->isDir()];
        }
        usort($entries, fn (array $a, array $b) => ($b['directory'] <=> $a['directory']) ?: strnatcasecmp($a['name'], $b['name']));

        return response()->json(['path' => $path, 'parent' => $path === $root ? null : dirname($path), 'entries' => $entries]);
    }

    public function store(StoreManualExecutionsRequest $request, ManualExecutionService $service): RedirectResponse
    {
        $count = $service->enqueue(Library::findOrFail($request->integer('library_id')), LibraryJobId::from($request->validated('job_id')), $request->validated('files'), $request->validated('mode') === 'now');
        Inertia::flash('toast', ['type' => 'success', 'message' => "{$count} file(s) queued. Playback pauses and processing schedules still apply."]);

        return to_route('executions.index');
    }
}
