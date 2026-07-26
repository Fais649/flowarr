<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLibraryRequest;
use App\Http\Requests\UpdateLibraryRequest;
use App\LibraryJobId;
use App\LibraryStatus;
use App\Models\Execution;
use App\Models\Library;
use App\Models\LibraryJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibrariesController extends Controller
{
    public function index(): Response
    {
        $libraries = Library::with('libraryJobs')->orderBy('created_at', 'desc')->get();

        return Inertia::render('libraries/index', [
            'libraries' => $libraries,
            'jobTypes' => collect(LibraryJobId::cases())->map(fn (LibraryJobId $id) => [
                'value' => $id->value,
                'label' => match ($id) {
                    LibraryJobId::TRANSCODE_MEDIA => 'Transcode Media',
                    LibraryJobId::EXTRACT_SUBTITLES => 'Extract Subtitles',
                    LibraryJobId::CONVERT_SUBTITLE => 'Convert Subtitles',
                },
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('libraries/create');
    }

    public function store(StoreLibraryRequest $request): RedirectResponse
    {
        Library::create([
            'base_path' => $request->base_path,
            'scan_interval' => $request->scan_interval,
            'status' => LibraryStatus::PENDING_SCAN,
        ]);

        return redirect()->route('libraries.index');
    }

    public function show(Library $library): Response
    {
        $library->load('libraryJobs');

        $recentExecutions = Execution::whereIn(
            'library_job_id',
            $library->libraryJobs->pluck('id')
        )
            ->with('libraryJob')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('libraries/[id]/index', [
            'library' => $library,
            'recentExecutions' => $recentExecutions,
            'jobTypes' => collect(LibraryJobId::cases())->map(fn (LibraryJobId $id) => [
                'value' => $id->value,
                'label' => match ($id) {
                    LibraryJobId::TRANSCODE_MEDIA => 'Transcode Media',
                    LibraryJobId::EXTRACT_SUBTITLES => 'Extract Subtitles',
                    LibraryJobId::CONVERT_SUBTITLE => 'Convert Subtitles',
                },
            ]),
        ]);
    }

    public function edit(Library $library): Response
    {
        return Inertia::render('libraries/create', ['library' => $library]);
    }

    public function update(UpdateLibraryRequest $request, Library $library): RedirectResponse
    {
        $library->update($request->validated());

        return redirect()->route('libraries.show', $library);
    }

    public function destroy(Library $library): RedirectResponse
    {
        $library->libraryJobs()->delete();
        $library->delete();

        return redirect()->route('libraries.index');
    }

    public function triggerScan(Library $library): RedirectResponse
    {
        $library->update(['status' => LibraryStatus::PENDING_SCAN]);

        return redirect()->route('libraries.show', $library);
    }

    public function toggleJob(Request $request, Library $library): RedirectResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'string', 'in:'.implode(',', array_map(fn (LibraryJobId $id) => $id->value, LibraryJobId::cases()))],
            'enabled' => ['required', 'boolean'],
        ]);

        $jobId = LibraryJobId::from($validated['job_id']);

        if ($validated['enabled']) {
            LibraryJob::firstOrCreate([
                'library_id' => $library->id,
                'job_id' => $jobId,
            ]);
        } else {
            $library->libraryJobs()
                ->where('job_id', $jobId)
                ->delete();
        }

        return redirect()->route('libraries.show', $library);
    }
}
